<?php

namespace Tests\Functional;

use App\Middleware\MockRequestHandler;
use App\Services\MockService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

class MockRequestHandlerTest extends TestCase
{
    public function testReturns404WhenNoMatchingMock(): void
    {
        $mockService = $this->createMock(MockService::class);
        $mockService->method('findMatchingMock')->willReturn(null);

        $handler = new MockRequestHandler($mockService, new NullLogger());

        $req = (new ServerRequestFactory())->createServerRequest('GET', 'http://localhost/mock/project/unknown');
        $res = new Response();
        $out = $handler($req, $res, ['project' => 'project', 'path' => 'unknown']);

        $this->assertSame(404, $out->getStatusCode());
        $this->assertSame('application/json', $out->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('No matching mock endpoint found', (string)$out->getBody());
    }

    public function testBuildsResponseFromMockService(): void
    {
        $mockData = [
            'mock' => [
                'id' => 1,
                'response_body' => ['msg' => 'ok'],
                'headers' => ['X-Header' => 'value', 'Content-Type' => 'application/custom'],
                'status_code' => 202,
            ],
            'path_params' => ['id' => '5']
        ];
        $generated = [
            'body' => ['msg' => 'ok'],
            'headers' => ['X-Header' => 'value', 'Content-Type' => 'application/custom'],
            'status_code' => 202,
        ];

        $mockService = $this->createMock(MockService::class);
        $mockService->method('findMatchingMock')->willReturn($mockData);
        $mockService->method('generateMockResponse')->willReturn($generated);

        $handler = new MockRequestHandler($mockService, new NullLogger());

        $req = (new ServerRequestFactory())->createServerRequest('GET', 'http://localhost/mock/project/api/foo');
        $res = new Response();
        $out = $handler($req, $res, ['project' => 'project', 'path' => 'api/foo']);

        $this->assertSame(202, $out->getStatusCode());
        $this->assertSame('application/custom', $out->getHeaderLine('Content-Type'));
        $this->assertSame('value', $out->getHeaderLine('X-Header'));
        $this->assertSame(json_encode(['msg' => 'ok']), (string)$out->getBody());
    }
}
