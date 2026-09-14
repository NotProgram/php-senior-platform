<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Enum\ProgressStatus;
use App\Service\LearningProgressService;
use App\Service\LessonContentService;
use Symfony\Component\DomCrawler\Crawler;

final class LessonFlowTest extends FunctionalTestCase
{
    private const ENTRY_LESSON = 'php-request-lifecycle';
    private const NEXT_LESSON = 'php-types-memory';

    public function testUnknownLessonReturns404(): void
    {
        $this->client->request('GET', '/lesson/does-not-exist');

        self::assertResponseStatusCodeSame(404);
    }

    public function testLockedLessonRedirectsToRoadmap(): void
    {
        $this->client->request('GET', '/lesson/' . self::NEXT_LESSON);

        self::assertResponseRedirects('/roadmap');
    }

    public function testCompletingALessonAwardsXpOnceAndUnlocksTheNextOne(): void
    {
        $crawler = $this->client->request('GET', '/lesson/' . self::ENTRY_LESSON);
        $startingXp = $this->reloadDefaultUser()->getExperiencePoints();
        $expectedXp = $startingXp + LearningProgressService::XP_PER_ADVANCE;

        $this->client->submit($crawler->selectButton('Marcar como Completada')->form());

        self::assertResponseRedirects('/lesson/' . self::ENTRY_LESSON);
        self::assertSame(ProgressStatus::Completed->value, $this->lessonStatus(self::ENTRY_LESSON));
        self::assertSame($expectedXp, $this->reloadDefaultUser()->getExperiencePoints());

        // Replaying the same request must not farm XP
        $this->client->request('POST', '/lesson/' . self::ENTRY_LESSON . '/complete', ['_csrf_token' => $this->csrfToken($crawler)]);
        self::assertSame($expectedXp, $this->reloadDefaultUser()->getExperiencePoints());

        $this->client->request('GET', '/lesson/' . self::NEXT_LESSON);
        self::assertResponseIsSuccessful();
    }

    public function testCrossSiteFormPostIsRejected(): void
    {
        $crawler = $this->client->request('GET', '/lesson/' . self::ENTRY_LESSON);

        $this->client->request(
            'POST',
            '/lesson/' . self::ENTRY_LESSON . '/complete',
            ['_csrf_token' => $this->csrfToken($crawler)],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'cross-site']
        );

        self::assertResponseStatusCodeSame(403);
        self::assertNotSame(ProgressStatus::Completed->value, $this->lessonStatus(self::ENTRY_LESSON));
    }

    public function testLessonActionsCannotSkipPrerequisites(): void
    {
        $crawler = $this->client->request('GET', '/lesson/' . self::ENTRY_LESSON);

        $this->client->request('POST', '/lesson/' . self::NEXT_LESSON . '/complete', ['_csrf_token' => $this->csrfToken($crawler)]);

        self::assertResponseRedirects('/roadmap');
        self::assertNull($this->lessonStatus(self::NEXT_LESSON));
    }

    public function testPerfectQuizMastersTheLesson(): void
    {
        $crawler = $this->client->request('GET', '/lesson/' . self::ENTRY_LESSON);
        $questions = static::getContainer()->get(LessonContentService::class)
            ->findLesson(self::ENTRY_LESSON)['quiz']['questions'];

        $form = $crawler->selectButton('Enviar Evaluación y Calificar')->form();
        foreach ($questions as $question) {
            $form['answers[' . $question['id'] . ']']->select($question['correct']);
        }
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(ProgressStatus::Mastered->value, $this->lessonStatus(self::ENTRY_LESSON));
    }

    public function testFailedExerciseShowsTheEvaluatorHints(): void
    {
        $crawler = $this->client->request('GET', '/lesson/' . self::ENTRY_LESSON);

        $this->client->submit($crawler->selectButton('Ejecutar y Validar Reto')->form([
            'submitted_code' => "<?php\necho 'hola';\n",
        ]));
        $this->client->followRedirect();

        self::assertStringContainsString('Falta declarar tipado estricto', (string) $this->client->getResponse()->getContent());
    }

    public function testSdlcRequirementsExercisePassesWithUserStorySpecification(): void
    {
        $crawler = $this->client->request('GET', '/lesson/se-sdlc-requirements');
        self::assertResponseIsSuccessful();

        $content = static::getContainer()->get(LessonContentService::class)
            ->findLesson('se-sdlc-requirements')['exercise']['solution_code'];

        $this->client->submit($crawler->selectButton('Ejecutar y Validar Reto')->form([
            'submitted_code' => $content,
        ]));

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertStringContainsString('Reto de código superado', (string) $this->client->getResponse()->getContent());
    }

    public function testCapstoneUnlocksAfterTheFirstGuidedProject(): void
    {
        $this->markLessonsCompleted('project-01-senior-crud', 'arch-pragmatic-ddd', 'devops-ci-cd-github-actions');

        $this->client->request('GET', '/lesson/project-07-capstone-distributed');

        self::assertResponseIsSuccessful();
    }

    private function csrfToken(Crawler $crawler): string
    {
        return (string) $crawler->filter('input[name="_csrf_token"]')->first()->attr('value');
    }
}
