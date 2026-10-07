<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*'));
        $exceptions->render(fn (ValidationException $e, Request $request) => $request->is('api/*') ? response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Invalid request data', 'fields' => $e->errors()]], 422) : null);
        $exceptions->render(fn (AuthenticationException $e, Request $request) => $request->is('api/*') ? response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Authentication required']], 401) : null);
        $exceptions->render(fn (AuthorizationException $e, Request $request) => $request->is('api/*') ? response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Insufficient permissions']], 403) : null);
        $exceptions->render(fn (ModelNotFoundException $e, Request $request) => $request->is('api/*') ? response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Resource not found']], 404) : null);
        $exceptions->render(fn (HttpExceptionInterface $e, Request $request) => $request->is('api/*') ? response()->json(['error' => ['code' => match ($e->getStatusCode()) {
            403 => 'FORBIDDEN', 409 => 'CONFLICT', 422 => 'VALIDATION_ERROR', default => 'REQUEST_ERROR'
        }, 'message' => $e->getMessage() ?: 'Request could not be completed']], $e->getStatusCode()) : null);
    })->create();
