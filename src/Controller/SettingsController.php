<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\LearningProgressService;
use App\Service\ProgressBackupService;
use App\Service\RoadmapService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends AbstractController
{
    private const CSRF_TOKEN_ID = 'submit';

    #[Route('/settings', name: 'app_settings')]
    public function index(
        UserRepository $userRepo,
        RoadmapService $roadmapService,
        ProgressBackupService $progressBackup
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('settings/index.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'auto_backup_info' => $progressBackup->getAutoBackupInfo(),
            'current_tab_title' => 'Settings.json',
            'breadcrumb_category' => 'preferences',
            'breadcrumb_current' => 'Configuración',
        ]);
    }

    #[Route('/settings/export', name: 'app_export_progress', methods: ['GET'])]
    public function exportProgress(
        UserRepository $userRepo,
        ProgressBackupService $progressBackup
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $data = $progressBackup->export($user);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $filename = sprintf('senior-devlab-progress-%s.json', date('Y-m-d'));

        $response = new Response($json, Response::HTTP_OK, [
            'Content-Type' => 'application/json',
        ]);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/settings/import', name: 'app_import_progress', methods: ['POST'])]
    public function importProgress(
        Request $request,
        UserRepository $userRepo,
        ProgressBackupService $progressBackup
    ): Response {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->getPayload()->getString('_csrf_token'))) {
            throw new AccessDeniedHttpException('Token CSRF inválido.');
        }

        $uploadedFile = $request->files->get('backup_file');
        if ($uploadedFile === null || !$uploadedFile->isValid()) {
            $this->addFlash('error', 'Debes seleccionar un archivo JSON válido para importar.');
            return $this->redirectToRoute('app_settings');
        }

        $content = file_get_contents($uploadedFile->getPathname());
        if ($content === false || trim($content) === '') {
            $this->addFlash('error', 'El archivo proporcionado está vacío.');
            return $this->redirectToRoute('app_settings');
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                throw new \InvalidArgumentException('El archivo no contiene un objeto JSON válido.');
            }

            $user = $userRepo->findOrCreateDefaultUser();
            $summary = $progressBackup->import($user, $data);

            $this->addFlash('success', sprintf(
                '¡Progreso restaurado con éxito! Se cargaron %d lección(es), %d quiz(zes), %d ejercicio(s) y %d XP.',
                $summary['lessons'],
                $summary['quiz_attempts'],
                $summary['exercise_attempts'],
                $summary['xp']
            ));
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Error al importar el progreso: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_settings');
    }

    #[Route('/settings/import-auto-backup', name: 'app_import_auto_backup', methods: ['POST'])]
    public function importAutoBackup(
        Request $request,
        UserRepository $userRepo,
        ProgressBackupService $progressBackup
    ): Response {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->getPayload()->getString('_csrf_token'))) {
            throw new AccessDeniedHttpException('Token CSRF inválido.');
        }

        try {
            $user = $userRepo->findOrCreateDefaultUser();
            $summary = $progressBackup->importFromAutoBackup($user);

            $this->addFlash('success', sprintf(
                '¡Progreso restaurado desde autoguardado local! (%d lecciones, %d XP).',
                $summary['lessons'],
                $summary['xp']
            ));
        } catch (\Throwable $e) {
            $this->addFlash('error', 'No se pudo restaurar el autoguardado: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_settings');
    }

    #[Route('/settings/profile', name: 'app_settings_update_profile', methods: ['POST'])]
    public function updateProfile(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $entityManager,
        ProgressBackupService $progressBackup
    ): Response {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->getPayload()->getString('_csrf_token'))) {
            throw new AccessDeniedHttpException('Token CSRF inválido.');
        }

        $displayName = trim($request->getPayload()->getString('display_name'));
        if ($displayName === '') {
            $this->addFlash('error', 'El nombre no puede estar vacío.');
            return $this->redirectToRoute('app_settings');
        }

        if (mb_strlen($displayName) > 100) {
            $this->addFlash('error', 'El nombre no puede superar los 100 caracteres.');
            return $this->redirectToRoute('app_settings');
        }

        $user = $userRepo->findOrCreateDefaultUser();
        $user->setDisplayName($displayName);
        $entityManager->flush();

        // Update auto-backup with the new name
        $progressBackup->autoSave($user);

        $this->addFlash('success', sprintf('Nombre de perfil actualizado a "%s".', $displayName));

        return $this->redirectToRoute('app_settings');
    }

    #[Route('/settings/reset-progress', name: 'app_settings_reset_progress', methods: ['POST'])]
    public function resetProgress(
        Request $request,
        UserRepository $userRepo,
        LearningProgressService $learningProgress
    ): Response {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->getPayload()->getString('_csrf_token'))) {
            throw new AccessDeniedHttpException('Token CSRF inválido.');
        }

        $learningProgress->resetProgress($userRepo->findOrCreateDefaultUser());

        $this->addFlash('success', 'Progreso de aprendizaje reiniciado a cero exitosamente. Puedes comenzar desde el inicio.');

        return $this->redirectToRoute('app_roadmap');
    }
}
