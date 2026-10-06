<?php

use App\Exceptions\MotifRejetManquantException;
use App\Exceptions\TransitionInterditeException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Toutes les erreurs de l'API sont rendues en JSON : { statusCode, message }, sans stack trace.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'statusCode' => 400,
                'message' => array_values(array_unique($e->validator->errors()->all())),
                'errors' => $e->errors(),
            ], 400);
        });

        $exceptions->render(fn (MotifRejetManquantException $e) => response()->json([
            'statusCode' => 400,
            'message' => $e->getMessage(),
        ], 400));

        $exceptions->render(fn (TransitionInterditeException $e) => response()->json([
            'statusCode' => 409,
            'message' => $e->getMessage(),
        ], 409));

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            $message = $e->getPrevious() instanceof ModelNotFoundException
                ? 'Demande introuvable.'
                : 'Ressource introuvable.';

            return response()->json(['statusCode' => 404, 'message' => $message], 404);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') || config('app.debug')) {
                return null;
            }
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            return response()->json([
                'statusCode' => $status,
                'message' => $status === 500 ? 'Erreur interne du serveur.' : $e->getMessage(),
            ], $status);
        });
    })->create();
