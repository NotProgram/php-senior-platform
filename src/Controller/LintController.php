<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PhpLinterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class LintController extends AbstractController
{
    #[Route('/api/lint', name: 'app_api_lint', methods: ['POST'])]
    public function lint(Request $request, PhpLinterService $linter): JsonResponse
    {
        $code = (string) $request->request->get('code', '');

        // If sent as JSON body
        if ($code === '' && $request->getContent() !== '') {
            $data = json_decode($request->getContent(), true);
            if (is_array($data) && isset($data['code'])) {
                $code = (string) $data['code'];
            }
        }

        $result = $linter->lint($code);

        return new JsonResponse($result);
    }
}
