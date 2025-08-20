<?php

use App\Controllers\MockController;
use App\Controllers\ProjectController;
use App\Middleware\MockRequestHandler;
use App\Middleware\SharedMockRequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    // Home route - redirect to dashboard
    $app->get('/', function (Request $request, Response $response) {
        return $response
            ->withHeader('Location', '/dashboard')
            ->withStatus(302);
    });
    
    // API routes
    $app->group('/api', function (Group $group) {
        // Project endpoints
        $group->group('/projects', function (Group $group) {
            $group->get('', [ProjectController::class, 'getAll']);
            $group->post('', [ProjectController::class, 'create']);
            $group->get('/{id}', [ProjectController::class, 'getOne']);
            $group->put('/{id}', [ProjectController::class, 'update']);
            $group->delete('/{id}', [ProjectController::class, 'delete']);
            $group->post('/{id}/export', [ProjectController::class, 'export']);
            $group->post('/import', [ProjectController::class, 'import']);
        });
        
        // Mock endpoints
        $group->group('/mocks', function (Group $group) {
            $group->get('', [MockController::class, 'getAll']);
            $group->post('', [MockController::class, 'create']);
            $group->get('/{id}', [MockController::class, 'getOne']);
            $group->put('/{id}', [MockController::class, 'update']);
            $group->delete('/{id}', [MockController::class, 'delete']);
        });
        
        // Shared project endpoint
        $group->get('/share/{token}', [ProjectController::class, 'getByShareToken']);
    });
    
    // Dashboard routes (web interface)
    $app->group('/dashboard', function (Group $group) {
        $group->get('', [\App\Controllers\DashboardController::class, 'index']);
        $group->get('/projects', [\App\Controllers\DashboardController::class, 'projects']);
        $group->get('/mocks', [\App\Controllers\DashboardController::class, 'mocks']);
        $group->get('/documentation', [\App\Controllers\DashboardController::class, 'documentation']);
    });
    
    // Mock request handler - this handles all dynamic mock endpoints
    $app->any('/mock/{project}/{path:.+}', MockRequestHandler::class);

    // Shared mock request handler - this handles mock endpoints via share token
    $app->any('/share/{token}/{path:.+}', SharedMockRequestHandler::class);
    
    // Handle OPTIONS requests for CORS preflight
    $app->options('/{routes:.+}', function (Request $request, Response $response) {
        return $response;
    });
    
    // 404 Not Found handler
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', 
        function (Request $request, Response $response) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Not Found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
    );
};