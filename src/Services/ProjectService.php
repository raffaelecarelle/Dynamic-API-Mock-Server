<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Database\Query\Builder;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class ProjectService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Builder
     */
    private $table;

    /**
     * ProjectService constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, Builder $table)
    {
        $this->logger = $logger;
        $this->table = $table;
    }

    /**
     * Get all projects
     *
     * @return array
     */
    public function getAllProjects(): array
    {
        return $this->table->get()->toArray();
    }

    /**
     * Get a project by ID
     *
     * @param int $id
     * @return array|null
     */
    public function getProjectById(int $id): ?array
    {
        $project = Project::find($id);
        return $project ? $project->toArray() : null;
    }

    /**
     * Create a new project
     *
     * @param array $data
     * @return array
     */
    public function createProject(array $data): array
    {
        $this->logger->info('Creating new project', ['data' => $data]);

        // Generate a share token if not provided
        if (!isset($data['share_token'])) {
            $data['share_token'] = Uuid::uuid4()->toString();
        }

        $project = new Project();
        $project->fill($data);
        $project->save();

        return $project->toArray();
    }

    /**
     * Update a project
     *
     * @param int $id
     * @param array $data
     * @return array|null
     */
    public function updateProject(int $id, array $data): ?array
    {
        $this->logger->info('Updating project', ['id' => $id, 'data' => $data]);

        $project = Project::find($id);
        if (!$project) {
            return null;
        }

        $project->fill($data);
        $project->save();

        return $project->toArray();
    }

    /**
     * Delete a project
     *
     * @param int $id
     * @return bool
     */
    public function deleteProject(int $id): bool
    {
        $this->logger->info('Deleting project', ['id' => $id]);

        $project = Project::find($id);
        if (!$project) {
            return false;
        }

        return (bool) $project->delete();
    }

    /**
     * Export a project with all its mocks
     *
     * @param int $id
     * @return array|null
     */
    public function exportProject(int $id): ?array
    {
        $project = Project::with('mocks')->find($id);
        if (!$project) {
            return null;
        }

        return $project->export();
    }

    /**
     * Import a project with its mocks
     *
     * @param array $data
     * @return array
     */
    public function importProject(array $data): array
    {
        $this->logger->info('Importing project');

        // Begin transaction
        \Illuminate\Database\Capsule\Manager::beginTransaction();

        try {
            // Extract project and mocks data
            $projectData = $data['project'] ?? [];
            $mocksData = $data['mocks'] ?? [];

            if (empty($projectData)) {
                throw new \InvalidArgumentException('Project data is required');
            }

            // Create the project
            $project = new Project();

            // Remove ID if present
            if (isset($projectData['id'])) {
                unset($projectData['id']);
            }

            // Generate a new share token
            $projectData['share_token'] = Uuid::uuid4()->toString();

            $project->fill($projectData);
            $project->save();

            // Create the mocks
            foreach ($mocksData as $mockData) {
                // Remove ID if present
                if (isset($mockData['id'])) {
                    unset($mockData['id']);
                }

                // Set the project ID
                $mockData['project_id'] = $project->id;

                // Create the mock
                $mock = new \App\Models\MockEndpoint();
                $mock->fill($mockData);
                $mock->save();
            }

            // Commit transaction
            \Illuminate\Database\Capsule\Manager::commit();

            return $project->export();
        } catch (\Exception $e) {
            // Rollback transaction
            \Illuminate\Database\Capsule\Manager::rollBack();

            $this->logger->error('Error importing project', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get a project by share token
     *
     * @param string $shareToken
     * @return array|null
     */
    public function getProjectByShareToken(string $shareToken): ?array
    {
        $this->logger->info('Getting project by share token', ['token' => $shareToken]);

        $project = Project::where('share_token', $shareToken)->first();
        if (!$project) {
            return null;
        }

        return $project->export();
    }
}
