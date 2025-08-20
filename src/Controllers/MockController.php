<?php

namespace App\Controllers;

use App\Services\MockService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class MockController
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
     * MockController constructor.
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
     * Get all mock endpoints
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getAll(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $projectId = isset($queryParams['project_id']) ? (int) $queryParams['project_id'] : null;

        $mocks = $this->mockService->getAllMocks($projectId);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $mocks
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get a mock endpoint by ID
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getOne(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $mock = $this->mockService->getMockById($id);

        if (!$mock) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Mock endpoint not found'
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $mock
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create a new mock endpoint
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        try {
            $mock = $this->mockService->createMock($data);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $mock
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\Exception $e) {
            $this->logger->error('Error creating mock endpoint', ['error' => $e->getMessage()]);

            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Error creating mock endpoint: ' . $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    /**
     * Update a mock endpoint
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $data = $request->getParsedBody();

        try {
            $mock = $this->mockService->updateMock($id, $data);

            if (!$mock) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Mock endpoint not found'
                ]));

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $mock
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error('Error updating mock endpoint', ['error' => $e->getMessage()]);

            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Error updating mock endpoint: ' . $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    /**
     * Delete a mock endpoint
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        $result = $this->mockService->deleteMock($id);

        if (!$result) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Mock endpoint not found'
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Mock endpoint deleted successfully'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
