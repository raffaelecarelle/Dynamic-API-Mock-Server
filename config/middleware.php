<?php

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Psr7\Response as Psr7Response;

return function (App $app) {
    $container = $app->getContainer();

    // Parse JSON, form data and xml
    $app->addBodyParsingMiddleware();

    // Add Content Length header to response
    $app->add(new ContentLengthMiddleware());

    // Add CORS middleware
    $app->add(function (Request $request, RequestHandler $handler) {
        $response = $handler->handle($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', $_ENV['CORS_ORIGIN'] ?? '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    });

    // Add request logging middleware
    $app->add(function (Request $request, RequestHandler $handler) use ($container) {
        $logger = $container->get(LoggerInterface::class);
        $logger->info('Request: ' . $request->getMethod() . ' ' . $request->getUri()->getPath(), [
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            'query' => $request->getQueryParams(),
            'body' => $request->getParsedBody()
        ]);

        $response = $handler->handle($request);

        $logger->info('Response: ' . $response->getStatusCode());
        return $response;
    });

    // Add error middleware
    $errorMiddleware = $app->addErrorMiddleware(
        $_ENV['DISPLAY_ERROR_DETAILS'] ?? true,
        $_ENV['LOG_ERRORS'] ?? true,
        $_ENV['LOG_ERROR_DETAILS'] ?? true,
        $container->get(LoggerInterface::class)
    );

    // Define custom error handler
    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    $errorHandler->forceContentType('application/json');
};
