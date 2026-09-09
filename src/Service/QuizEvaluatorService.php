<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\QuizAttempt;
use App\Entity\User;
use App\Entity\UserProgress;
use App\Enum\ProgressStatus;
use App\Repository\UserProgressRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuizEvaluatorService
{
    public function __construct(
        private readonly LessonContentService $contentService,
        private readonly UserProgressRepository $progressRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * @param array<string, string> $submittedAnswers
     * @return array<string, mixed>
     */
    public function evaluate(User $user, string $lessonSlug, array $submittedAnswers): array
    {
        $lessonData = $this->contentService->getLessonDetails($lessonSlug);
        $questions = $lessonData['quiz']['questions'] ?? [];

        if (empty($questions)) {
            return [
                'success' => false,
                'message' => 'No hay evaluación técnica disponible para esta lección.',
            ];
        }

        $totalQuestions = count($questions);
        $correctCount = 0;
        $review = [];

        foreach ($questions as $q) {
            $qId = $q['id'];
            $userAns = $submittedAnswers[$qId] ?? '';
            $correctAns = $q['correct'];
            $isCorrect = ($userAns === $correctAns);

            if ($isCorrect) {
                $correctCount++;
            }

            $review[] = [
                'question_id' => $qId,
                'question' => $q['question'],
                'user_answer' => $userAns,
                'correct_answer' => $correctAns,
                'is_correct' => $isCorrect,
                'explanation' => $q['explanation'],
            ];
        }

        $percentage = (int) round(($correctCount / $totalQuestions) * 100);
        $passed = ($percentage >= 80);

        // Record QuizAttempt in database
        $attempt = new QuizAttempt($user, $lessonSlug, $submittedAnswers, $percentage, $passed);
        $this->entityManager->persist($attempt);

        // Update UserProgress
        $progress = $this->progressRepository->findProgress($user, $lessonSlug);
        if ($progress === null) {
            $progress = new UserProgress($user, $this->resolveModuleSlug($lessonSlug), $lessonSlug);
            $this->entityManager->persist($progress);
        }

        if ($passed) {
            $user->addExperiencePoints(50);
            $progress->setScore(max($progress->getScore(), $percentage));
            
            // If already completed or high score, elevate to MASTERED
            if ($percentage === 100) {
                $progress->setStatus(ProgressStatus::Mastered->value);
            } else {
                $progress->setStatus(ProgressStatus::Completed->value);
            }
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'passed' => $passed,
            'score_percentage' => $percentage,
            'correct_count' => $correctCount,
            'total_questions' => $totalQuestions,
            'review' => $review,
        ];
    }

    private function resolveModuleSlug(string $lessonSlug): string
    {
        if (str_starts_with($lessonSlug, 'poo-')) {
            return 'poo';
        }
        if (str_starts_with($lessonSlug, 'symfony-')) {
            return 'symfony';
        }
        if (str_starts_with($lessonSlug, 'twig-')) {
            return 'twig';
        }
        if (str_starts_with($lessonSlug, 'sql-')) {
            return 'databases';
        }
        if (str_starts_with($lessonSlug, 'doctrine-')) {
            return 'doctrine';
        }
        if (str_starts_with($lessonSlug, 'arch-') || str_starts_with($lessonSlug, 'system-design-')) {
            return 'architecture';
        }

        return 'php-fundamentals';
    }
}
