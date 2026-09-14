<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Lesson content lives in content/lessons/<slug>.php, one file returning an array per lesson,
 * so each lesson can be edited and reviewed on its own (OPcache keeps the arrays in shared memory).
 */
class LessonContentService
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    private const OPTION_KEYS = ['a', 'b', 'c', 'd', 'e', 'f'];

    private readonly string $lessonsDirectory;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $loadedLessons = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%/content/lessons')]
        ?string $lessonsDirectory = null,
    ) {
        $this->lessonsDirectory = $lessonsDirectory ?? dirname(__DIR__, 2) . '/content/lessons';
    }

    /**
     * @return array<string, mixed>|null null when the slug has no content file
     */
    public function findLesson(string $slug): ?array
    {
        if (isset($this->loadedLessons[$slug])) {
            return $this->loadedLessons[$slug];
        }

        if (preg_match(self::SLUG_PATTERN, $slug) !== 1) {
            return null;
        }

        $file = $this->lessonsDirectory . '/' . $slug . '.php';
        if (!is_file($file)) {
            return null;
        }

        $lesson = require $file;
        if (!is_array($lesson)) {
            return null;
        }

        $lesson['quiz'] = $this->normalizeQuiz($lesson);
        $lesson = $this->normalizeLesson($lesson);

        return $this->loadedLessons[$slug] = $lesson;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLessonDetails(string $slug): array
    {
        return $this->findLesson($slug)
            ?? $this->findLesson('php-request-lifecycle')
            ?? [];
    }

    public function hasLesson(string $slug): bool
    {
        return $this->findLesson($slug) !== null;
    }

    /**
     * @return list<string>
     */
    public function lessonSlugs(): array
    {
        $files = glob($this->lessonsDirectory . '/*.php') ?? [];
        $slugs = [];
        foreach ($files as $file) {
            $slugs [] = basename($file, '.php');
        }
        sort($slugs);

        return $slugs;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAllLessons(): array
    {
        $all = [];
        foreach ($this->lessonSlugs() as $slug) {
            $lesson = $this->findLesson($slug);
            if ($lesson !== null) {
                $all[$slug] = $lesson;
            }
        }

        return $all;
    }

    /**
     * The corpus stores quizzes in two historical shapes: an already structured
     * {title, questions} map, and a bare list of questions whose options are a
     * positional array. Normalising both to the structured shape here keeps the
     * template, the evaluator and the flashcard generator on one contract.
     *
     * @param array<string, mixed> $lesson
     * @return array{title: string, questions: list<array{id: string, question: string, options: array<string, string>, correct: string, explanation: string}>}
     */
    private function normalizeQuiz(array $lesson): array
    {
        $raw = $lesson['quiz'] ?? [];
        $title = is_array($raw) && isset($raw['title'])
            ? (string) $raw['title']
            : 'Evaluación Técnica: ' . (string) ($lesson['title'] ?? 'Lección');

        $rawQuestions = [];
        if (is_array($raw)) {
            $rawQuestions = isset($raw['questions']) && is_array($raw['questions'])
                ? $raw['questions']
                : array_filter($raw, static fn($item): bool => is_array($item) && isset($item['question']));
        }

        $questions = [];
        $position = 0;

        foreach ($rawQuestions as $question) {
            $position++;
            $options = [];
            $index = 0;

            foreach ((array) ($question['options'] ?? []) as $key => $text) {
                $letter = is_string($key) && in_array($key, self::OPTION_KEYS, true)
                    ? $key
                    : (self::OPTION_KEYS[$index] ?? (string) $index);
                $options[$letter] = (string) $text;
                $index++;
            }

            $correct = $question['correct'] ?? ($question['correct_answer'] ?? 0);
            $correctKey = is_int($correct)
                ? (self::OPTION_KEYS[$correct] ?? 'a')
                : (string) $correct;

            $questions[] = [
                'id' => (string) ($question['id'] ?? 'q' . $position),
                'question' => (string) ($question['question'] ?? ''),
                'options' => $options,
                'correct' => $correctKey,
                'explanation' => (string) ($question['explanation'] ?? ''),
            ];
        }

        return ['title' => $title, 'questions' => $questions];
    }

    /**
     * @param array<string, mixed> $lesson
     * @return array<string, mixed>
     */
    private function normalizeLesson(array $lesson): array
    {
        if (isset($lesson['architecture_code'])) {
            if (is_string($lesson['architecture_code'])) {
                $lesson['architecture_code'] = [
                    'filename' => 'ArchitectureReference.php',
                    'title' => 'Implementación Arquitectónica de Referencia',
                    'tag' => 'PHO 8.4 Senior Pattern',
                    'code' => $lesson['architecture_code'],
                ];
            } elseif (is_array($lesson['architecture_code'])) {
                $lesson['architecture_code']['filename'] ??= 'ArchitectureReference.php';
                $lesson['architecture_code']['title'] ??= 'Implementación Arquitectónica de Referencia';
                $lesson['architecture_code']['tag'] ??= 'PHP 8.4 Senior Pattern';
                $lesson['architecture_code']['code'] ??= '';
            }
        }

        if (isset($lesson['deep_dive'])) {
            if (is_string($lesson['deep_dive'])) {
                $lesson['deep_dive'] = [
                    [
                        'title' => 'Fundamentos y Análisis Profundo',
                        'icon' => 'book-open',
                        'content' => $lesson['deep_dive'],
                        'code' => null,
                        'takeaways' => null,
                    ],
                ];
            } elseif (is_array($lesson['deep_dive'])) {
                foreach ($lesson['deep_dive'] as $k => $sec) {
                    if (is_string($sec)) {
                        $lesson['deep_dive'][$k] = [
                            'title' => 'Profundización Técnica',
                            'icon' => 'book-open',
                            'content' => $sec,
                            'code' => null,
                            'takeaways' => null,
                        ];
                    }
                }
            }
        }

        if (isset($lesson['senior_mindset'])) {
            if (is_string($lesson['senior_mindset'])) {
                $lesson['senior_mindset'] = [
                    'thought_process' => $lesson['senior_mindset'],
                    'critical_questions' => [],
                ];
            } elseif (is_array($lesson['senior_mindset'])) {
                $lesson['senior_mindset']['thought_process'] ??= '';
                $lesson['senior_mindset']['critical_questions'] ??= [];
            }
        }

        if (isset($lesson['junior_vs_senior']) && is_array($lesson['junior_vs_senior'])) {
            $jvs = $lesson['junior_vs_senior'];
            $junior = $jvs['junior'] ?? null;
            $senior = $jvs['senior'] ?? null;

            if (is_string($junior)) {
                $jvs['junior'] = [
                    'title' => 'Enfoque Inicial',
                    'approach' => $junior,
                    'code' => null,
                    'flaws' => [],
                ];
            } elseif (is_array($junior)) {
                $jvs['junior']['approach'] ??= $junior['title'] ?? '';
                $jvs['junior']['flaws'] ??= $junior['consequences'] ?? [];
            }

            if (is_string($senior)) {
                $jvs['senior'] = [
                    'title' => 'Enfoque Senior',
                    'approach' => $senior,
                    'code' => null,
                    'rationale' => [],
                    'trade_offs' => null,
                ];
            } elseif (is_array($senior)) {
                $jvs['senior']['approach'] ??= $senior['title'] ?? '';
                $jvs['senior']['rationale'] ??= $senior['benefits'] ?? [];
            }

            $jvs['problem_statement'] ??= 'Escenario de ingeniería y arquitectura en producción.';
            $lesson['junior_vs_senior'] = $jvs;
        }

        if (isset($lesson['exercise']) && is_array($lesson['exercise'])) {
            $ex = $lesson['exercise'];
            if (isset($ex['guide'])) {
                if (is_string($ex['guide'])) {
                    $ex['guide'] = [
                        'explanation' => $ex['guide'],
                        'steps' => [],
                        'useful_functions' => [],
                    ];
                } elseif (is_array($ex['guide'])) {
                    $ex['guide']['xplanation'] ??= '';
                    $ex['guide']['steps'] ??= [];
                    $ex['guide']['useful_functions'] ??= [];
                }
            }

            if (isset($ex['hints']) && is_array($ex['hints'])) {
                $normalizedHints = [];
                foreach ($ex['hints'] as $idx => $hint) {
                    if (is_string($hint)) {
                        $label = 'Pista ' . ($idx + 1);
                        $text = $hint;
                        if (preg_match('/^\%([*?\)\\s*(*)$/s', $hint, $m)) {
                            $label = $m[1];
                            $text = $m[2];
                        }
                        $normalizedHints[] = [
                            'label' => $label,
                            'text' => $text,
                            'snippet' => null,
                        ];
                    } elseif (is_array($hint)) {
                        $normalizedHints[] = [
                            'label' => (string) ($hint['label'] ?? ('Pista ' . ($idx + 1))),
                            'text' => (string) ($hint['text'] ?? ($hint['content'] ?? '')),
                            'snippet' => $hint['snippet'] ?? null,
                        ];
                    }
                }
                $ex['hints'] = $normalizedHints;
            }
            $lesson['exercise'] = $ex;
        }

        return $lesson;
    }
}
