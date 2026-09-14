<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ReviewCard;
use App\Entity\User;
use App\Repository\ReviewCardRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

/**
 * SM-2 style scheduling for the review deck.
 *
 * Grades: 0 «otra vez», 1 «difícil», 2 «bien», 3 «fácil». A lapse resets the
 * repetition count and brings the card back within the same session, which is
 * what makes spaced repetition work on material that was not actually recalled.
 */
class SpacedRepetitionService
{
    public const GRADE_AGAIN = 0;
    public const GRADE_HARD = 1;
    public const GRADE_GOOD = 2;
    public const GRADE_EASY = 3;

    private const NEW_CARDS_PER_SESSION = 8;
    private const SESSION_LIMIT = 20;
    private const LAPSE_MINUTES = 10;
    private const XP_PER_CARD = 3;

    public function __construct(
        private readonly FlashcardService $flashcardService,
        private readonly ReviewCardRepository $reviewCardRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * Builds the study queue: everything overdue first (oldest due date wins),
     * then a capped number of unseen cards so a session stays finishable.
     *
     * @return array{cards: list<array<string, mixed>>, due_total: int, fresh_total: int, deck_total: int}
     */
    public function buildSession(User $user, ?string $lessonSlug = null, int $limit = self::SESSION_LIMIT): array
    {
        $deck = $lessonSlug !== null
            ? $this->flashcardService->deckForLesson($lessonSlug)
            : $this->flashcardService->deckForUser($user);

        $states = $this->reviewCardRepository->getStateMap($user);
        $now = new DateTimeImmutable();

        $due = [];
        $fresh = [];

        foreach ($deck as $card) {
            $state = $states[$card['id']] ?? null;

            if ($state === null) {
                $fresh[] = $card + [
                    'is_new' => true,
                    'interval_days' => 0,
                    'repetitions' => 0,
                    'due_at' => null,
                ];
                continue;
            }

            if (!$state->isDue($now)) {
                continue;
            }

            $due[] = $card + [
                'is_new' => false,
                'interval_days' => $state->getIntervalDays(),
                'repetitions' => $state->getRepetitions(),
                'due_at' => $state->getDueAt()->format('Y-m-d H:i'),
                'due_sort' => $state->getDueAt()->getTimestamp(),
            ];
        }

        usort($due, static fn(array $a, array $b): int => $a['due_sort'] <=> $b['due_sort']);

        $newAllowance = $lessonSlug !== null ? $limit : self::NEW_CARDS_PER_SESSION;
        $queue = array_merge(
            array_slice($due, 0, $limit),
            array_slice($fresh, 0, $newAllowance)
        );

        return [
            'cards' => array_slice($queue, 0, $limit),
            'due_total' => count($due),
            'fresh_total' => count($fresh),
            'deck_total' => count($deck),
        ];
    }

    /**
     * @return array{card_id: string, grade: int, interval_days: int, ease: float, due_at: string, due_human: string, repetitions: int}
     */
    public function grade(User $user, string $cardId, int $grade): array
    {
        if (!in_array($grade, [self::GRADE_AGAIN, self::GRADE_HARD, self::GRADE_GOOD, self::GRADE_EASY], true)) {
            throw new InvalidArgumentException(sprintf('Calificación inválida: %d. Se esperaba 0-3.', $grade));
        }

        $meta = $this->resolveCardMeta($cardId);
        if ($meta === null) {
            throw new InvalidArgumentException(sprintf('La tarjeta "%s" no existe en el mazo generado.', $cardId));
        }

        $card = $this->reviewCardRepository->findForUser($user, $cardId);
        if ($card === null) {
            $card = new ReviewCard($user, $cardId, $meta['lesson_slug'], $meta['kind']);
            $this->entityManager->persist($card);
        }

        $schedule = $this->computeSchedule(
            $card->getEaseFactor(),
            $card->getIntervalDays(),
            $card->getRepetitions(),
            $grade
        );

        $card->reschedule(
            $schedule['ease'],
            $schedule['interval_days'],
            $schedule['repetitions'],
            $schedule['due_at'],
            $grade === self::GRADE_AGAIN
        );

        $user->addExperiencePoints(self::XP_PER_CARD);
        $user->touchLastActive();
        $this->entityManager->flush();

        return [
            'card_id' => $cardId,
            'grade' => $grade,
            'interval_days' => $card->getIntervalDays(),
            'ease' => round($card->getEaseFactor(), 2),
            'due_at' => $card->getDueAt()->format('Y-m-d H:i'),
            'due_human' => $this->humanizeInterval($schedule['interval_days'], $grade),
            'repetitions' => $card->getRepetitions(),
        ];
    }

    /**
     * Pure scheduling arithmetic, kept separate so it is trivially testable.
     *
     * @return array{ease: float, interval_days: int, repetitions: int, due_at: DateTimeImmutable}
     */
    public function computeSchedule(float $ease, int $intervalDays, int $repetitions, int $grade): array
    {
        $now = new DateTimeImmutable();

        if ($grade === self::GRADE_AGAIN) {
            return [
                'ease' => $ease - 0.2,
                'interval_days' => 0,
                'repetitions' => 0,
                'due_at' => $now->modify(sprintf('+%d minutes', self::LAPSE_MINUTES)),
            ];
        }

        $ease += match ($grade) {
            self::GRADE_HARD => -0.15,
            self::GRADE_EASY => 0.15,
            default => 0.0,
        };
        $ease = max(ReviewCard::EASE_MIN, min(ReviewCard::EASE_MAX, $ease));

        $repetitions++;

        if ($repetitions === 1) {
            $next = $grade === self::GRADE_EASY ? 3 : 1;
        } elseif ($repetitions === 2) {
            $next = $grade === self::GRADE_HARD ? 3 : 6;
        } else {
            $multiplier = match ($grade) {
                self::GRADE_HARD => 1.2,
                self::GRADE_EASY => $ease * 1.3,
                default => $ease,
            };
            $next = (int) max(1, round(max(1, $intervalDays) * $multiplier));
        }

        $next = min($next, 365);

        return [
            'ease' => $ease,
            'interval_days' => $next,
            'repetitions' => $repetitions,
            'due_at' => $now->modify(sprintf('+%d days', $next)),
        ];
    }

    /**
     * @return array{tracked: int, due: int, mature: int, lapses: int}
     */
    public function stats(User $user): array
    {
        return $this->reviewCardRepository->statsFor($user, new DateTimeImmutable());
    }

    /**
     * @return array<string, int>
     */
    public function forecast(User $user, int $days = 14): array
    {
        return $this->reviewCardRepository->forecast($user, new DateTimeImmutable('today'), $days);
    }

    public function countDue(User $user): int
    {
        return $this->reviewCardRepository->countDue($user, new DateTimeImmutable());
    }

    /**
     * A card id is only accepted when the generator can still produce it, which
     * prevents arbitrary rows from being created through the grading endpoint.
     *
     * @return array{lesson_slug: string, kind: string}|null
     */
    private function resolveCardMeta(string $cardId): ?array
    {
        $parts = explode('#', $cardId);
        if (count($parts) !== 3) {
            return null;
        }

        [$lessonSlug, $kind] = $parts;

        foreach ($this->flashcardService->deckForLesson($lessonSlug) as $card) {
            if ($card['id'] === $cardId) {
                return ['lesson_slug' => $lessonSlug, 'kind' => $kind];
            }
        }

        return null;
    }

    private function humanizeInterval(int $days, int $grade): string
    {
        if ($grade === self::GRADE_AGAIN) {
            return sprintf('vuelve en %d minutos', self::LAPSE_MINUTES);
        }
        if ($days === 1) {
            return 'mañana';
        }
        if ($days < 30) {
            return sprintf('en %d días', $days);
        }

        return sprintf('en %d meses', (int) round($days / 30));
    }
}
