<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\CurriculumGraph;
use App\Service\LessonContentService;
use App\Service\PrerequisiteEngine;
use App\Service\RoadmapService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Guards the hand-written curriculum data: roadmap, lesson content files and prerequisite graph must agree.
 */
final class CurriculumIntegrityTest extends KernelTestCase
{
    /**
     * @var array<string, string> lesson slug => owning module slug
     */
    private array $roadmapLessons = [];

    protected function setUp(): void
    {
        self::bootKernel();

        foreach (self::getContainer()->get(RoadmapService::class)->getSections() as $moduleSlug => $module) {
            foreach ($module['lessons'] ?? [] as $lesson) {
                $this->roadmapLessons[$lesson['slug']] = $moduleSlug;
            }
        }
    }

    public function testEveryRoadmapLessonHasContentAndAnExercise(): void
    {
        $content = self::getContainer()->get(LessonContentService::class);

        foreach (array_keys($this->roadmapLessons) as $slug) {
            $lesson = $content->findLesson($slug);

            self::assertNotNull($lesson, "Missing content/lessons/$slug.php");
            self::assertSame($slug, $lesson['slug']);
            self::assertIsString($lesson['exercise']['starter_code'] ?? null, "$slug has no exercise starter code");
        }
    }

    public function testEveryQuizUsesTheCanonicalShape(): void
    {
        $content = self::getContainer()->get(LessonContentService::class);

        foreach (array_keys($this->roadmapLessons) as $slug) {
            $quiz = $content->findLesson($slug)['quiz'] ?? [];

            self::assertIsString($quiz['title'] ?? null, "$slug quiz has no title");
            self::assertNotEmpty($quiz['questions'] ?? [], "$slug quiz has no questions");

            $ids = array_column($quiz['questions'], 'id');
            self::assertCount(count($quiz['questions']), array_unique($ids), "$slug has missing or duplicate question ids");

            foreach ($quiz['questions'] as $question) {
                self::assertArrayHasKey($question['correct'], $question['options'], "$slug/{$question['id']}: correct answer is not one of the options");
                self::assertNotEmpty($question['explanation'], "$slug/{$question['id']} has no explanation");
            }
        }
    }

    /**
     * templates/lesson/show.html.twig renders a single lesson schema; other shapes crash in strict mode
     * (dev/test) and silently drop content in production.
     */
    public function testEveryLessonMatchesTheSchemaTheTemplateRenders(): void
    {
        $content = self::getContainer()->get(LessonContentService::class);

        foreach (array_keys($this->roadmapLessons) as $slug) {
            $lesson = $content->findLesson($slug);

            self::assertIsString($lesson['senior_mindset']['thought_process'] ?? null, "$slug: senior_mindset.thought_process");
            self::assertIsList($lesson['senior_mindset']['critical_questions'] ?? null, "$slug: senior_mindset.critical_questions");
            self::assertIsString($lesson['architecture_code']['code'] ?? null, "$slug: architecture_code.code");
            self::assertIsList($lesson['deep_dive'] ?? [], "$slug: deep_dive");

            foreach (['junior' => 'flaws', 'senior' => 'rationale'] as $side => $points) {
                self::assertIsString($lesson['junior_vs_senior'][$side]['approach'] ?? null, "$slug: junior_vs_senior.$side.approach");
                self::assertIsList($lesson['junior_vs_senior'][$side][$points] ?? null, "$slug: junior_vs_senior.$side.$points");
            }
            self::assertIsList($lesson['junior_vs_senior']['senior']['trade_offs'] ?? null, "$slug: junior_vs_senior.senior.trade_offs");

            self::assertIsString($lesson['exercise']['instructions'] ?? null, "$slug: exercise.instructions");
            if (isset($lesson['exercise']['guide'])) {
                self::assertArrayNotHasKey(0, $lesson['exercise']['guide'], "$slug: exercise.guide must be {explanation, steps, useful_functions}");
            }
            foreach ($lesson['exercise']['hints'] ?? [] as $i => $hint) {
                self::assertIsString($hint['text'] ?? null, "$slug: exercise.hints.$i.text");
            }
        }
    }

    public function testPrerequisitesOnlyReferenceRoadmapLessons(): void
    {
        $map = $this->prerequisiteMap();

        self::assertSame(
            [],
            array_values(array_diff(array_keys($this->roadmapLessons), array_keys($map))),
            'Roadmap lessons without a prerequisite entry stay locked forever'
        );

        foreach ($map as $slug => $prerequisites) {
            self::assertArrayHasKey($slug, $this->roadmapLessons, "Prerequisite entry '$slug' is not in the roadmap");
            foreach ($prerequisites as $prerequisite) {
                self::assertArrayHasKey($prerequisite, $this->roadmapLessons, "'$slug' requires unknown lesson '$prerequisite'");
            }
        }
    }

    public function testEveryLessonCanBeUnlockedFromTheEntryPoints(): void
    {
        $map = $this->prerequisiteMap();
        $unlocked = [];

        do {
            $unlockedSomething = false;
            foreach ($map as $slug => $prerequisites) {
                if (!isset($unlocked[$slug]) && array_diff($prerequisites, array_keys($unlocked)) === []) {
                    $unlocked[$slug] = true;
                    $unlockedSomething = true;
                }
            }
        } while ($unlockedSomething);

        self::assertSame([], array_values(array_diff(array_keys($this->roadmapLessons), array_keys($unlocked))));
    }

    public function testModuleSlugIsResolvedFromTheRoadmap(): void
    {
        $roadmap = self::getContainer()->get(RoadmapService::class);

        self::assertSame('git', $roadmap->getModuleSlugForLesson('git-branching-strategies'));
        self::assertSame('software-engineering', $roadmap->getModuleSlugForLesson('se-solid-principles'));

        $this->expectException(\InvalidArgumentException::class);
        $roadmap->getModuleSlugForLesson('not-a-lesson');
    }

    /**
     * @return array<string, list<string>>
     */
    private function prerequisiteMap(): array
    {
        return self::getContainer()->get(CurriculumGraph::class)->prerequisites();
    }
}
