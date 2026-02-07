<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ErrorController extends AbstractController
{
    public function show(Throwable $exception): Response
    {
        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $message = $this->getPublicMessage($exception, $statusCode);

        return $this->render('error/error.html.twig', [
            'status_code' => $statusCode,
            'message' => $message,
        ], new Response('', $statusCode));
    }

    private function getPublicMessage(Throwable $exception, int $statusCode): string
    {
        // В проде не показываем технические сообщения
        return match ($statusCode) {
            403 => 'У вас нет прав для доступа к этой странице.',
            404 => 'Страница не найдена.',
            500 => 'Произошла внутренняя ошибка сервера.',
            default => 'Произошла ошибка.',
        };
    }
}
