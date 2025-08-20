<?php

namespace App\Controllers;

use App\Services\MockService;
use App\Services\ProjectService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class DashboardController
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
     * DashboardController constructor.
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
     * Render the main dashboard
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function index(Request $request, Response $response): Response
    {
        // Get template content
        $template = file_get_contents(__DIR__ . '/../../public/templates/layout.html');
        $content = file_get_contents(__DIR__ . '/../../public/templates/dashboard.html');
        
        // Replace content placeholder
        $html = str_replace('{{content}}', $content, $template);
        
        // Add page title
        $html = str_replace('{{title}}', 'Dashboard', $html);
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    /**
     * Render the projects page
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function projects(Request $request, Response $response): Response
    {
        // Get template content
        $template = file_get_contents(__DIR__ . '/../../public/templates/layout.html');
        $content = file_get_contents(__DIR__ . '/../../public/templates/projects.html');
        
        // Replace content placeholder
        $html = str_replace('{{content}}', $content, $template);
        
        // Add page title
        $html = str_replace('{{title}}', 'Projects', $html);
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    /**
     * Render the mocks page
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function mocks(Request $request, Response $response): Response
    {
        // Get template content
        $template = file_get_contents(__DIR__ . '/../../public/templates/layout.html');
        $content = file_get_contents(__DIR__ . '/../../public/templates/mocks.html');
        
        // Replace content placeholder
        $html = str_replace('{{content}}', $content, $template);
        
        // Add page title
        $html = str_replace('{{title}}', 'Mock Endpoints', $html);
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
    
    /**
     * Render the documentation page
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function documentation(Request $request, Response $response): Response
    {
        // Get template content
        $template = file_get_contents(__DIR__ . '/../../public/templates/layout.html');
        $content = file_get_contents(__DIR__ . '/../../public/templates/documentation.html');
        
        // Replace content placeholder
        $html = str_replace('{{content}}', $content, $template);
        
        // Add page title
        $html = str_replace('{{title}}', 'API Documentation', $html);
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
}