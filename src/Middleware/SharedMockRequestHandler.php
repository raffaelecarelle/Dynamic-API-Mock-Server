<?php

namespace App\Middleware;

use App\Services\MockService;
use App\Services\ProjectService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;

class SharedMockRequestHandler
{
    /**
     * @var ProjectService
     */
    private $projectService;

    /**
     * @var MockService
     */
    private $mockService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SharedMockRequestHandler constructor.
     *
     * @param ProjectService $projectService
     * @param MockService $mockService
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProjectService $projectService,
        MockService $mockService,
        LoggerInterface $logger
    ) {
        $this->projectService = $projectService;
        $this->mockService = $mockService;
        $this->logger = $logger;
    }

    /**
     * Handle the shared mock request
     *
     * @param Request $request
     * @param RequestHandler $handler
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $this->logger->info('Handling shared mock request', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri()
        ]);

        // Extract token and path from the request
        $routeArguments = $request->getAttribute('route')->getArguments();
        $shareToken = $routeArguments['token'] ?? '';
        $path = $routeArguments['path'] ?? '';

        // Find project by share token
        $project = $this->projectService->getProjectByShareToken($shareToken);

        // If no project is found, return 404
        if (!$project || empty($project['project']['id'])) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Shared project not found'
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // Find a matching mock endpoint
        $matchingMock = $this->mockService->findMatchingMock(
            $project['project']['id'],
            $request->getMethod(),
            $path
        );

        // If no matching mock is found, return 404
        if (!$matchingMock) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'No matching mock endpoint found'
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // Extract request data
        $requestData = [
            'path_params' => $matchingMock['path_params'] ?? [],
            'query_params' => $request->getQueryParams(),
            'body' => $request->getParsedBody() ?? []
        ];

        // Generate the mock response
        $mockResponse = $this->mockService->generateMockResponse(
            $matchingMock['mock'],
            $requestData
        );

        // Create the response
        $response = new \Slim\Psr7\Response();

        // Set the response body
        $responseBody = $mockResponse['body'] ?? [];
        $response->getBody()->write(json_encode($responseBody));

        // Set the response headers
        $responseHeaders = $mockResponse['headers'] ?? [];
        foreach ($responseHeaders as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        // Set the content type if not already set
        if (!isset($responseHeaders['Content-Type'])) {
            $response = $response->withHeader('Content-Type', 'application/json');
        }

        // Set the status code
        $statusCode = $mockResponse['status_code'] ?? 200;
        $response = $response->withStatus($statusCode);

        return $response;
    }
}
