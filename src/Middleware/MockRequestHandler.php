<?php

namespace App\Middleware;

use App\Services\MockService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class MockRequestHandler
{
    /**
     * @var MockService
     */
    private $mockService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * MockRequestHandler constructor.
     *
     * @param MockService $mockService
     * @param LoggerInterface $logger
     */
    public function __construct(MockService $mockService, LoggerInterface $logger)
    {
        $this->mockService = $mockService;
        $this->logger = $logger;
    }

    /**
     * Handle the mock request
     *
     * @param Request $request
     * @param Response $response
     * @param array $routeArguments
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $routeArguments): Response
    {
        $this->logger->info('Handling mock request', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri()
        ]);

        // Extract project and path from the request
        $projectSlug = $routeArguments['project'] ?? '';
        $path = '/'.$routeArguments['path'] ?? '';

        // Find a matching mock endpoint
        $matchingMock = $this->mockService->findMatchingMock(
            $projectSlug,
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
