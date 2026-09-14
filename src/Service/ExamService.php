<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\QuizAttempt;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Interleaved practice exams.
 *
 * Studying one module at a time produces fluency that collapses under mixed
 * questioning — which is exactly what a technical panel does. This service
 * interleaves questions from every lesson the learner has already opened,
 * spreading them across modules, and reports mastery per module instead of a
 * single opaque score.
 *
 * Exams are stateless: a question key carries its lesson, so the submission can
 * be graded from the corpus without storing the generated exam anywhere.
 */
class ExamService
{
    public const PASS_THRESHOLD = 75;
    private const KEY_SEPARATOR = '::';

    public function __construct(
        private readonly LessonContentService $contentService,
        private readonly FlashcardService $flashcardService,
        private readonly RoadmapService $roadmapService,
        private readonly CurriculumGraph $graph,
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * @return array{questions: list<array<string, mixed>>, modules: list<string>, pool: int}
     */
    public function buildExam(User $user, int $questionCount = 12): array
    {
        $pool = [];

        foreach ($this->flashcardService->studiedLessonSlugs($user) as $lessonSlug) {
            if (!$this->contentService->hasLesson($lessonSlug)) {
                continue;
            }

            $details = $this->contentService->getLessonDetails($lessonSlug);
            foreach ($details['quiz']['questions'] as $question) {
                $pool[$this->moduleOf($lessonSlug)][] = [
                    'key' => $lessonSlug . self::KEY_SEPARATOR . $question['id'],
                    'lesson_slug' => $lessonSlug,
                    'lesson_title' => (string) ($details['title'] ?? $lessonSlug),
                    'module' => $this->moduleTitle($lessonSlug),
                    'question' => $question['question'],
                    'options' => $question['options'],
                ];
            }
        }

        if ($pool === []) {
            return ['questions' => [], 'modules' => [], 'pool' => 0];
        }

        // Round-robin across modules so no single topic dominates the exam.
        foreach ($pool as $moduleSlug => $questions) {
            shuffle($questions);
            $pool[$moduleSlug] = $questions;
        }

        $modules = array_keys($pool);
        shuffle($modules);

        $selected = [];
        $poolSize = 0;
        foreach ($pool as $questions) {
            $poolSize += count($questions);
        }

        while (count($selected) < $questionCount && $modules !== []) {
            foreach ($modules as $position => $moduleSlug) {
                if (count($selected) >= $questionCount) {
                    break;
                }
                $question = array_shift($pool[$moduleSlug]);
                if ($question === null) {
                    unset($modules[$position]);
                    continue;
                }
                $selected[] = $question;
            }
            $modules = array_values($modules);
        }

        return [
            'questions' => $selected,
            'modules' => array_values(array_unique(array_map(
                static fn(array $question): string => $question['module'],
                $selected
            ))),
            'pool' => $poolSize,
        ];
    }

    /**
     * Grades a submission and produces a per-module mastery breakdown plus the
     * lessons worth revisiting first.
     *
     * @param array<string, string> $answers question key => chosen option letter
     * @return array{total: int, correct: int, score: int, passed: bool, review: list<array<string, mixed>>, per_module: list<array<string, mixed>>, weakest: list<array<string, mixed>>}
     */
    public function evaluate(User $user, array $answers): array
    {
        $review = [];
        $byModule = [];
        $byLesson = [];
        $correctCount = 0;

        foreach ($answers as $key => $chosen) {
            $parts = explode(self::KEY_SEPARATOR, (string) $key);
            if (count($parts) !== 2) {
                continue;
            }

            [$lessonSlug, $questionId] = $parts;
            if (!$this->contentService->hasLesson($lessonSlug)) {
                continue;
            }

            $details = $this->contentService->getLessonDetails($lessonSlug);
            $question = null;
            foreach ($details['quiz']['questions'] as $candidate) {
                if ($candidate['id'] === $questionId) {
                    $question = $candidate;
                    break;
                }
            }
            if ($question === null) {
                continue;
            }

            $isCorrect = $question['correct'] === (string) $chosen;
            if ($isCorrect) {
                $correctCount++;
            }

            $moduleTitle = $this->moduleTitle($lessonSlug);
            $byModule[$moduleTitle]['total'] = ($byModule[$moduleTitle]['total'] ?? 0) + 1;
            $byModule[$moduleTitle]['correct'] = ($byModule[$moduleTitle]['correct'] ?? 0) + ($isCorrect ? 1 : 0);

            $byLesson[$lessonSlug]['title'] = (string) ($details['title'] ?? $lessonSlug);
            $byLesson[$lessonSlug]['total'] = ($byLesson[$lessonSlug]['total'] ?? 0) + 1;
            $byLesson[$lessonSlug]['misses'] = ($byLesson[$lessonSlug]['misses'] ?? 0) + ($isCorrect ? 0 : 1);

            $review[] = [
                'lesson_slug' => $lessonSlug,
                'lesson_title' => (string) ($details['title'] ?? $lessonSlug),
                'module' => $moduleTitle,
                'question' => $question['question'],
                'options' => $question['options'],
                'chosen' => (string) $chosen,
                'correct' => $question['correct'],
                'is_correct' => $isCorrect,
                'explanation' => $question['explanation'],
            ];
        }

        $total = count($review);
        $score = $total > 0 ? (int) round(($correctCount / $total) * 100) : 0;
        $passed = $score >= self::PASS_THRESHOLD;

        $perModule = [];
        foreach ($byModule as $module => $counts) {
            $perModule[] = [
                'module' => $module,
                'total' => $counts['total'],
                'correct' => $counts['correct'],
                'percent' => (int) round(($counts['correct'] / $counts['total']) * 100),
            ];
        }
        usort($perModule, static fn(array $a, array $b): int => $a['percent'] <=> $b['percent']);

        $weakest = [];
        foreach ($byLesson as $slug => $data) {
            if (($data['misses'] ?? 0) === 0) {
                continue;
            }
            $weakest[] = [
                'slug' => $slug,
                'title' => $data['title'],
                'misses' => $data['misses'],
                'total' => $data['total'],
            ];
        }
        usort($weakest, static fn(array $a, array $b): int => $b['misses'] <=> $a['misses']);

        if ($total > 0) {
            $this->entityManager->persist(new QuizAttempt($user, 'exam-interleaved', $answers, $score, $passed));
            $user->addExperiencePoints($passed ? 60 : 15);
            $user->touchLastActive();
            $this->entityManager->flush();
        }

        return [
            'total' => $total,
            'correct' => $correctCount,
            'score' => $score,
            'passed' => $passed,
            'review' => $review,
            'per_module' => $perModule,
            'weakest' => array_slice($weakest, 0, 4),
        ];
    }

    private function moduleOf(string $lessonSlug): string
    {
        return $this->graph->moduleOf($lessonSlug);
    }

    private function moduleTitle(string $lessonSlug): string
    {
        $sections = $this->roadmapService->getSections();
        $moduleSlug = $this->moduleOf($lessonSlug);

        return (string) ($sections[$moduleSlug]['title'] ?? ucfirst($moduleSlug));
    }
}
