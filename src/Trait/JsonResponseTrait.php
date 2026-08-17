<?php

namespace App\Trait;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait JsonResponseTrait
{
    /**
     * Return JSON response with status for AJAX calls.
     */
    protected function jsonResponse(string $type, string $message, string $title = '', int $status = Response::HTTP_OK, array $extraData = []): JsonResponse
    {
        $data = [
            'status' => $status,
            'type' => $type,
            'message' => $message,
            'title' => $title,
        ];

        if (!empty($extraData)) {
            $data = array_merge($data, $extraData);
        }

        return new JsonResponse($data, $status);
    }

    /**
     * Get unique ID value as requested.
     */
    protected function getUniqIdValue(): string
    {
        // Nota bene: Using uniqid to generate a unique string based on the current microtime.
        return uniqid('', true);
    }
}
