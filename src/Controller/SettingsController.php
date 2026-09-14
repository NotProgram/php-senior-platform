<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\RoadmapService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'app_settings')]
    public function index(UserRepository $userRepo, RoadmapService $roadmapService): Response
    {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('settings/index.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'current_tab_title' => 'Settings.json',
            'breadcrumb_category' => 'preferences',
            'breadcrumb_current' => 'Configuración',
        ]);
    }

    #[Route('/settings/reset-progress', name: 'app_settings_reset_progress', methods: ['POST'])]
    public function resetProgress(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();

        $conn = $em->getConnection();
        $conn->executeStatement('DELETE FROM user_progress WHERE user_id = ?', [$user->getId()]);
        $conn->executeStatement('DELETE FROM quiz_attempts WHERE user_id = ?', [$user->getId()]);
        $conn->executeStatement('DELETE FROM exercise_attempts WHERE user_id = ?', [$user->getId()]);
        $conn->executeStatement('DELETE FROM review_cards WHERE user_id = ?', [$user->getId()]);

        $user->setCurrentLevel('Junior')
            ->setStreakDays(1);
        $conn->executeStatement('UPDATE users SET experience_points = 0 WHERE id = ?', [$user->getId()]);

        $this->addFlash('success', 'Progreso de aprendizaje reiniciado a cero exitosamente. Puedes comenzar desde el inicio.');

        return $this->redirectToRoute('app_roadmap');
    }
}
