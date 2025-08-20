<?php

namespace App\Services;

use App\Models\MockEndpoint;
use App\Models\Project;
use Illuminate\Database\Query\Builder;
use Psr\Log\LoggerInterface;

class MockService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseGeneratorService
     */
    private $responseGenerator;

    /**
     * @var Builder
     */
    private $table;

    /**
     * MockService constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseGeneratorService $responseGenerator
     */
    public function __construct(LoggerInterface $logger, ResponseGeneratorService $responseGenerator, Builder $table)
    {
        $this->logger = $logger;
        $this->responseGenerator = $responseGenerator;
        $this->table = $table;
    }

    /**
     * Get all mock endpoints
     *
     * @param int|null $projectId Filter by project ID
     * @return array
     */
    public function getAllMocks(?int $projectId = null): array
    {
        $query = MockEndpoint::query();

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get a mock endpoint by ID
     *
     * @param int $id
     * @return array|null
     */
    public function getMockById(int $id): ?array
    {
        $mock = MockEndpoint::find($id);
        return $mock ? $mock->toArray() : null;
    }

    /**
     * Create a new mock endpoint
     *
     * @param array $data
     * @return array
     */
    public function createMock(array $data): array
    {
        $this->logger->info('Creating new mock endpoint', ['data' => $data]);

        // Validate project exists
        $project = Project::find($data['project_id'] ?? null);
        if (!$project) {
            throw new \InvalidArgumentException('Project not found');
        }

        // Create the mock endpoint
        $mock = new MockEndpoint();
        $mock->fill($data);
        $mock->save();

        return $mock->toArray();
    }

    /**
     * Update a mock endpoint
     *
     * @param int $id
     * @param array $data
     * @return array|null
     */
    public function updateMock(int $id, array $data): ?array
    {
        $this->logger->info('Updating mock endpoint', ['id' => $id, 'data' => $data]);

        $mock = MockEndpoint::find($id);
        if (!$mock) {
            return null;
        }

        // If project_id is being changed, validate the new project exists
        if (isset($data['project_id']) && $data['project_id'] !== $mock->project_id) {
            $project = Project::find($data['project_id']);
            if (!$project) {
                throw new \InvalidArgumentException('Project not found');
            }
        }

        $mock->fill($data);
        $mock->save();

        return $mock->toArray();
    }

    /**
     * Delete a mock endpoint
     *
     * @param int $id
     * @return bool
     */
    public function deleteMock(int $id): bool
    {
        $this->logger->info('Deleting mock endpoint', ['id' => $id]);

        $mock = MockEndpoint::find($id);
        if (!$mock) {
            return false;
        }

        return (bool) $mock->delete();
    }

    /**
     * Find a mock endpoint that matches the request
     *
     * @param string $projectSlug
     * @param string $method
     * @param string $path
     * @return array|null
     */
    public function findMatchingMock(string $projectSlug, string $method, string $path): ?array
    {
        $this->logger->info('Finding matching mock', [
            'project' => $projectSlug,
            'method' => $method,
            'path' => $path
        ]);

        // Find the project by slug or ID
        $project = is_numeric($projectSlug)
            ? Project::find($projectSlug)
            : Project::where('name', $projectSlug)->first();

        if (!$project) {
            $this->logger->warning('Project not found', ['project' => $projectSlug]);
            return null;
        }

        // Get all mocks for this project with the matching method
        $mocks = MockEndpoint::where('project_id', $project->id)
            ->where('method', strtoupper($method))
            ->get();

        // Find the first mock that matches the path
        foreach ($mocks as $mock) {
            if ($mock->matchesPath($path)) {
                $this->logger->info('Found matching mock', ['id' => $mock->id]);

                // Extract path parameters
                $pathParams = $mock->extractPathParams($path);

                return [
                    'mock' => $mock->toArray(),
                    'path_params' => $pathParams
                ];
            }
        }

        $this->logger->warning('No matching mock found');
        return null;
    }

    /**
     * Generate a response for a mock endpoint
     *
     * @param array $mockData
     * @param array $requestData
     * @return array
     */
    public function generateMockResponse(array $mockData, array $requestData): array
    {
        $this->logger->info('Generating mock response', [
            'mock_id' => $mockData['id'] ?? 'unknown',
            'request' => $requestData
        ]);

        // Apply delay if configured
        $delay = $mockData['delay'] ?? 0;
        if ($delay > 0) {
            $maxDelay = $_ENV['MAX_DELAY'] ?? 5000;
            $delay = min($delay, $maxDelay);
            usleep($delay * 1000); // Convert to microseconds
        }

        // Generate the response
        return $this->responseGenerator->generateResponse($mockData, $requestData);
    }
}
