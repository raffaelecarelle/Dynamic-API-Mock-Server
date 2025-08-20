<?php

namespace App\Controllers;

use App\Services\ProjectService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ProjectController
{
    /**
     * @var ProjectService
     */
    private $projectService;
    
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ProjectController constructor.
     *
     * @param ProjectService $projectService
     * @param LoggerInterface $logger
     */
    public function __construct(ProjectService $projectService, LoggerInterface $logger)
    {
        $this->projectService = $projectService;
        $this->logger = $logger;
    }

    /**
     * Get all projects
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getAll(Request $request, Response $response): Response
    {
        $projects = $this->projectService->getAllProjects();
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $projects
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get a project by ID
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getOne(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $project = $this->projectService->getProjectById($id);
        
        if (!$project) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Project not found'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $project
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create a new project
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        try {
            $project = $this->projectService->createProject($data);
            
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $project
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\Exception $e) {
            $this->logger->error('Error creating project', ['error' => $e->getMessage()]);
            
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Error creating project: ' . $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    /**
     * Update a project
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
            $project = $this->projectService->updateProject($id, $data);
            
            if (!$project) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Project not found'
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
            
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $project
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error('Error updating project', ['error' => $e->getMessage()]);
            
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Error updating project: ' . $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    /**
     * Delete a project
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        
        $result = $this->projectService->deleteProject($id);
        
        if (!$result) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Project not found'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Project deleted successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Export a project
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function export(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        
        $exportData = $this->projectService->exportProject($id);
        
        if (!$exportData) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Project not found'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $exportData
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Import a project
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function import(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        try {
            $project = $this->projectService->importProject($data);
            
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $project,
                'message' => 'Project imported successfully'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\Exception $e) {
            $this->logger->error('Error importing project', ['error' => $e->getMessage()]);
            
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Error importing project: ' . $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }
    
    /**
     * Get a project by share token
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getByShareToken(Request $request, Response $response, array $args): Response
    {
        $shareToken = $args['token'] ?? '';
        
        if (empty($shareToken)) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Share token is required'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        $project = $this->projectService->getProjectByShareToken($shareToken);
        
        if (!$project) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Project not found or not shared'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $project
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}