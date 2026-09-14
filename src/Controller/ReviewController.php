<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\RoadmapService;
use App\Service\SpacedRepetitionService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReviewController extends AbstractController
{
    #[Route('/repaso', name: 'app_review')]
    public function index(
        Request $request,
        UserRepository $userRepo,
        RoadmapService $roadmapService,
        SpacedRepetitionService $spacedRepetition
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $lessonSlug = $request->query->get('lesson');
        $session = $spacedRepetition->buildSession(
            $user,
            $lessonSlug !== null && $lessonSlug !== '' ? (string) $lessonSlug : null
        );

        return $this->render('review/index.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $roadmapService->getSections(),
            'session' => $session,
            'lesson_filter' => $lessonSlug,
            'current_tab_title' => 'Flashcards.deck',
            'breadcrumb_category' => 'retrieval',
            'breadcrumb_current' => 'Repaso Espaciado',
        ]);
    }

    #[Route('/repaso/grade', name: 'app_review_grade', methods: ['POST'])]
    public function grade(
        Request $request,
        UserRepository $userRepo,
        SpacedRepetitionService $spacedRepetition
    ): JsonResponse {
        $user = $userRepo->findOrCreateDefaultUser();

        $data = json_decode($request->getContent(), true);
        $cardId = (string) ($data['card_id'] ?? $request->request->get('card_id', ''));
        $grade = (int) ($data['grade'] ?? $request->request->get('grade', 0));

        if ($cardId === '') {
            return new JsonResponse(['error' => 'ID de tarjeta no proporcionado'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $spacedRepetition->grade($user, $cardId, $grade);

            return new JsonResponse([
                'success' => true,
                'result' => $result,
                'due_remaining' => $spacedRepetition->countDue($user),
            ]);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
