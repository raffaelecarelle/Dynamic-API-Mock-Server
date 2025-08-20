<?php

namespace Tests\Functional;

use App\Controllers\MockController;
use App\Services\MockService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

class MockControllerTest extends TestCase
{
    private function controller(MockService $service): MockController
    {
        return new MockController($service, new NullLogger());
    }

    public function testGetOneNotFound(): void
    {
        $service = $this->createMock(MockService::class);
        $service->method('getMockById')->willReturn(null);
        $controller = $this->controller($service);

        $req = (new ServerRequestFactory())->createServerRequest('GET', '/api/mocks/999');
        $res = new Response();
        $out = $controller->getOne($req, $res, ['id' => '999']);

        $this->assertSame(404, $out->getStatusCode());
        $this->assertSame('application/json', $out->getHeaderLine('Content-Type'));
    }

    public function testCreateSuccess(): void
    {
        $service = $this->createMock(MockService::class);
        $service->method('createMock')->willReturn(['id' => 1, 'path' => '/api/test']);
        $controller = $this->controller($service);

        $req = (new ServerRequestFactory())->createServerRequest('POST', '/api/mocks')
            ->withParsedBody(['project_id' => 1, 'path' => '/api/test']);
        $res = new Response();
        $out = $controller->create($req, $res);

        $this->assertSame(201, $out->getStatusCode());
        $this->assertStringContainsString('success', (string)$out->getBody());
    }

    public function testCreateError(): void
    {
        $service = $this->createMock(MockService::class);
        $service->method('createMock')->willThrowException(new \InvalidArgumentException('Project not found'));
        $controller = $this->controller($service);

        $req = (new ServerRequestFactory())->createServerRequest('POST', '/api/mocks')
            ->withParsedBody(['project_id' => 999]);
        $res = new Response();
        $out = $controller->create($req, $res);

        $this->assertSame(400, $out->getStatusCode());
        $this->assertStringContainsString('error', (string)$out->getBody());
    }

    public function testUpdateNotFound(): void
    {
        $service = $this->createMock(MockService::class);
        $service->method('updateMock')->willReturn(null);
        $controller = $this->controller($service);

        $req = (new ServerRequestFactory())->createServerRequest('PUT', '/api/mocks/1')->withParsedBody([]);
        $res = new Response();
        $out = $controller->update($req, $res, ['id' => '1']);

        $this->assertSame(404, $out->getStatusCode());
    }

    public function testDeleteNotFound(): void
    {
        $service = $this->createMock(MockService::class);
        $service->method('deleteMock')->willReturn(false);
        $controller = $this->controller($service);

        $req = (new ServerRequestFactory())->createServerRequest('DELETE', '/api/mocks/1');
        $res = new Response();
        $out = $controller->delete($req, $res, ['id' => '1']);

        $this->assertSame(404, $out->getStatusCode());
    }

    public function testDeleteSuccess(): void
    {
        $service = $this->createMock(MockService::class);
        $service->method('deleteMock')->willReturn(true);
        $controller = $this->controller($service);

        $req = (new ServerRequestFactory())->createServerRequest('DELETE', '/api/mocks/1');
        $res = new Response();
        $out = $controller->delete($req, $res, ['id' => '1']);

        $this->assertSame(200, $out->getStatusCode());
        $this->assertStringContainsString('success', (string)$out->getBody());
    }
}
