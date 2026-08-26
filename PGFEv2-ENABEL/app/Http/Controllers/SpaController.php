<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Sert le build Vue (public/index.html) pour le même domaine que l'API.
 */
final class SpaController extends Controller
{
    public function __invoke(): SymfonyResponse
    {
        $index = public_path('index.html');

        if (! is_file($index)) {
            abort(503, 'Frontend SPA non déployé (public/index.html manquant).');
        }

        return response(
            file_get_contents($index),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }
}
