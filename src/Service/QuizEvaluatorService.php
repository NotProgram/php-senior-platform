<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\QuizAttempt;
use App\Entity\User;
use App\Enum\ProgressStatus;
use Doctrine\ORM\EntityManagerInterface;

class QuizEvaluatorService
{
    public const PASSING_PERCENTAGE = 80;

    public function __construct(
        private readonly LessonContentService $contentService,
        private readonly LearningProgressService $learningProgress,
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * @param array<array-key, mixed> $submittedAnswers raw "answers" form field, keyed by question id
     *
     * @return array{success: false, message: string}|array{success: true, passed: bool, score_percentage: int, correct_count: int, total_questions: int, xp_awarded: int, review: list<array<string, mixed>>}
     */
    public function evaluate(User $user, string $lessonSlug, array $submittedAnswers): array
    {
        $questions = $this->contentService->findLesson($lessonSlug)['quiz']['questions'] ?? [];

        if ($questions === []) {
            return [
                'success' => false,
                'message' => 'No hay evaluación técnica disponible para esta lección.',
            ];
        }

        // Only keep one string answer per known question: the attempt is persisted as JSON
        $answers = [];
        $correctCount = 0;
        $review = [];

        foreach ($questions as $question) {
            $questionId = $question['id'];
            $answer = $submittedAnswers[$questionId] ?? '';
            $answers[$questionId] = is_string($answer) ? $answer : '';
            $isCorrect = $answers[$questionId] === $question['correct'];

            if ($isCorrect) {
                $correctCount++;
            }

            $review[] = [
                'question_id' => $questionId,
                'question' => $question['question'],
                'user_answer' => $answers[$questionId],
                'correct_answer' => $question['correct'],
                'is_correct' => $isCorrect,
                'explanation' => $question['explanation'],
            ];
        }

        $percentage = (int) round(($correctCount / count($questions)) * 100);
        $passed = $percentage >= self::PASSING_PERCENTAGE;

        $this->entityManager->persist(new QuizAttempt($user, $lessonSlug, $answers, $percentage, $passed));

        $xpAwarded = 0;
        if ($passed) {
            $progress = $this->learningProgress->findOrCreateProgress($user, $lessonSlug);
            $progress->setScore(max($progress->getScore(), $percentage));
            $xpAwarded = $this->learningProgress->advance(
                $user,
                $progress,
                $percentage === 100 ? ProgressStatus::Mastered : ProgressStatus::Completed
            );
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'passed' => $passed,
            'score_percentage' => $percentage,
            'correct_count' => $correctCount,
            'total_questions' => count($questions),
            'xp_awarded' => $xpAwarded,
            'review' => $review,
        ];
    }
}
