<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\LintRequest;
use App\Service\PhpLinterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class LintController extends AbstractController
{
    #[Route('/api/lint', name: 'app_api_lint', methods: ['POST'])]
    public function lint(#[MapRequestPayload] LintRequest $payload, PhpLinterService $linter): JsonResponse
    {
        return $this->json($linter->lint($payload->code));
    }
}
