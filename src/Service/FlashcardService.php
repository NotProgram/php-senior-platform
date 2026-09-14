<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\UserProgress;
use App\Repository\UserProgressRepository;

/**
 * Derives retrieval-practice cards from the lesson corpus.
 *
 * Nothing is authored twice: every card is a projection of existing lesson
 * content (quiz stems, canonical quotes, internal phases, deep-dive takeaways,
 * senior reasoning), so the deck grows automatically with the curriculum.
 * Card ids are deterministic, which is what lets scheduling state survive
 * content edits.
 */
class FlashcardService
{
    private const PER_LESSON_LIMITS = [
        'recall' => 3,
        'proceso' => 3,
        'principio' => 2,
        'insight' => 2,
        'criterio' => 1,
    ];

    private const KIND_LABELS = [
        'recall' => 'Recuperación activa',
        'proceso' => 'Proceso interno',
        'principio' => 'Principio canónico',
        'insight' => 'Conclusión clave',
        'criterio' => 'Criterio senior',
    ];

    public function __construct(
        private readonly LessonContentService $contentService,
        private readonly RoadmapService $roadmapService,
        private readonly UserProgressRepository $progressRepository
    ) {}

    /**
     * Lessons the learner has actually opened: practising material never seen
     * is guessing, not retrieval.
     *
     * @return list<string>
     */
    public function studiedLessonSlugs(User $user): array
    {
        $studied = [];

        foreach ($this->progressRepository->getProgressMapForUser($user) as $slug => $progress) {
            if (in_array($progress->getStatus(), [
                UserProgress::STATUS_IN_PROGRESS,
                UserProgress::STATUS_COMPLETED,
                UserProgress::STATUS_MASTERED,
            ], true)) {
                $studied[] = $slug;
            }
        }

        return $studied;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function deckForUser(User $user): array
    {
        $cards = [];

        foreach ($this->studiedLessonSlugs($user) as $slug) {
            foreach ($this->deckForLesson($slug) as $card) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function deckForLesson(string $lessonSlug): array
    {
        if (!$this->contentService->hasLesson($lessonSlug)) {
            return [];
        }

        $details = $this->contentService->getLessonDetails($lessonSlug);
        $meta = $this->lessonMeta($lessonSlug);
        $cards = [];

        foreach ($this->buildRecallCards($details) as $index => $card) {
            $cards[] = $this->decorate($card, 'recall', $index, $lessonSlug, $meta);
        }
        foreach ($this->buildProcessCards($details) as $index => $card) {
            $cards[] = $this->decorate($card, 'proceso', $index, $lessonSlug, $meta);
        }
        foreach ($this->buildPrincipleCards($details) as $index => $card) {
            $cards[] = $this->decorate($card, 'principio', $index, $lessonSlug, $meta);
        }
        foreach ($this->buildInsightCards($details) as $index => $card) {
            $cards[] = $this->decorate($card, 'insight', $index, $lessonSlug, $meta);
        }
        foreach ($this->buildMindsetCards($details) as $index => $card) {
            $cards[] = $this->decorate($card, 'criterio', $index, $lessonSlug, $meta);
        }

        return $cards;
    }

    /**
     * @param array{prompt: string, answer: string, detail?: string, hint?: string} $card
     * @param array{title: string, module: string} $meta
     * @return array<string, mixed>
     */
    private function decorate(array $card, string $kind, int $index, string $lessonSlug, array $meta): array
    {
        return [
            'id' => sprintf('%s#%s#%d', $lessonSlug, $kind, $index),
            'lesson_slug' => $lessonSlug,
            'lesson_title' => $meta['title'],
            'module' => $meta['module'],
            'kind' => $kind,
            'kind_label' => self::KIND_LABELS[$kind],
            'prompt' => $card['prompt'],
            'answer' => $card['answer'],
            'detail' => $card['detail'] ?? '',
            'hint' => $card['hint'] ?? '',
        ];
    }

    /**
     * @param array<string, mixed> $details
     * @return list<array{prompt: string, answer: string, detail?: string, hint?: string}>
     */
    private function buildRecallCards(array $details): array
    {
        $cards = [];

        foreach (($details['quiz']['questions'] ?? []) as $question) {
            if (count($cards) >= self::PER_LESSON_LIMITS['recall']) {
                break;
            }

            $correctKey = $question['correct'];
            $answer = $question['options'][$correctKey] ?? '';
            if ($answer === '') {
                continue;
            }

            $cards[] = [
                'prompt' => $question['question'],
                'answer' => $answer,
                'detail' => $question['explanation'],
                'hint' => 'Responde en voz alta antes de revelar: si dudas, marca «Otra vez».',
            ];
        }

        return $cards;
    }

    /**
     * @param array<string, mixed> $details
     * @return list<array{prompt: string, answer: string, detail?: string, hint?: string}>
     */
    private function buildProcessCards(array $details): array
    {
        $internals = $details['internals'] ?? null;
        if (!is_array($internals) || !isset($internals['steps'])) {
            return [];
        }

        $cards = [];
        foreach ($internals['steps'] as $step) {
            if (count($cards) >= self::PER_LESSON_LIMITS['proceso']) {
                break;
            }

            $cards[] = [
                'prompt' => sprintf('En «%s», ¿qué ocurre exactamente en la fase «%s»?', (string) ($internals['title'] ?? 'el flujo interno'), (string) ($step['phase'] ?? '')),
                'answer' => (string) ($step['description'] ?? ''),
                'hint' => 'Describe el mecanismo, no el nombre de la fase.',
            ];
        }

        return $cards;
    }

    /**
     * @param array<string, mixed> $details
     * @return list<array{prompt: string, answer: string, detail?: string, hint?: string}>
     */
    private function buildPrincipleCards(array $details): array
    {
        $cards = [];

        foreach (($details['citations'] ?? []) as $citation) {
            if (count($cards) >= self::PER_LESSON_LIMITS['principio']) {
                break;
            }

            $cards[] = [
                'prompt' => sprintf('¿Qué sostiene %s sobre «%s» y por qué cambia tus decisiones?', (string) ($citation['author'] ?? 'la fuente canónica'), (string) ($citation['topic'] ?? '')),
                'answer' => (string) ($citation['quote'] ?? ''),
                'detail' => (string) ($citation['explanation'] ?? ''),
                'hint' => sprintf('Fuente: %s', (string) ($citation['source'] ?? 'bibliografía del módulo')),
            ];
        }

        return $cards;
    }

    /**
     * @param array<string, mixed> $details
     * @return list<array{prompt: string, answer: string, detail?: string, hint?: string}>
     */
    private function buildInsightCards(array $details): array
    {
        $cards = [];

        foreach (($details['deep_dive'] ?? []) as $section) {
            if (count($cards) >= self::PER_LESSON_LIMITS['insight']) {
                break;
            }
            $takeaway = (string) ($section['takeaways'] ?? '');
            if ($takeaway === '') {
                continue;
            }

            $cards[] = [
                'prompt' => sprintf('¿Cuál es la conclusión operativa de «%s»?', (string) ($section['title'] ?? 'la sección')),
                'answer' => $takeaway,
                'hint' => 'Una frase que puedas aplicar mañana en producción.',
            ];
        }

        return $cards;
    }

    /**
     * @param array<string, mixed> $details
     * @return list<array{prompt: string, answer: string, detail?: string, hint?: string}>
     */
    private function buildMindsetCards(array $details): array
    {
        $mindset = $details['senior_mindset'] ?? null;
        if (is_array($mindset)) {
            $mindset = (string) ($mindset['thought_process'] ?? '');
        }
        $mindset = (string) $mindset;

        if ($mindset === '') {
            return [];
        }

        return [[
            'prompt' => sprintf('Ante «%s», ¿en qué se diferencia el razonamiento senior del junior?', (string) ($details['title'] ?? 'este tema')),
            'answer' => $mindset,
            'hint' => 'Piensa en fallos, costos y reversibilidad, no en sintaxis.',
        ]];
    }

    /**
     * @return array{title: string, module: string}
     */
    private function lessonMeta(string $lessonSlug): array
    {
        foreach ($this->roadmapService->getSections() as $module) {
            if (!isset($module['lessons'])) {
                continue;
            }
            foreach ($module['lessons'] as $lesson) {
                if ($lesson['slug'] === $lessonSlug) {
                    return [
                        'title' => (string) $lesson['title'],
                        'module' => (string) ($module['title'] ?? 'Currículo'),
                    ];
                }
            }
        }

        return ['title' => $lessonSlug, 'module' => 'Currículo'];
    }
}
