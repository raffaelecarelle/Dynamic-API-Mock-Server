<?php

namespace Tests\Unit;

use App\Services\MockService;
use App\Services\ResponseGeneratorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class MockServiceTest extends TestCase
{
    public function testGenerateMockResponseAppliesDelayAndDelegates(): void
    {
        // Prepare a stub for ResponseGeneratorService
        $responseGenerator = $this->createMock(ResponseGeneratorService::class);
        $responseGenerator->method('generateResponse')->willReturn([
            'body' => ['ok' => true],
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200
        ]);

        // Instantiate MockService without running its constructor
        $ref = new \ReflectionClass(MockService::class);
        $service = $ref->newInstanceWithoutConstructor();

        // Inject private properties
        $propLogger = $ref->getProperty('logger');
        $propLogger->setAccessible(true);
        $propLogger->setValue($service, new NullLogger());

        $propResp = $ref->getProperty('responseGenerator');
        $propResp->setAccessible(true);
        $propResp->setValue($service, $responseGenerator);

        // Apply a very small delay and ensure MAX_DELAY environment doesn't block
        $_ENV['MAX_DELAY'] = 50; // ms
        $mockData = [
            'id' => 1,
            'delay' => 5
        ];
        $requestData = [];

        $start = microtime(true);
        $result = $service->generateMockResponse($mockData, $requestData);
        $elapsedMs = (microtime(true) - $start) * 1000;

        $this->assertGreaterThanOrEqual(4.0, $elapsedMs, 'Expected at least ~5ms delay');
        $this->assertSame(['ok' => true], $result['body']);
        $this->assertSame(200, $result['status_code']);
    }
}
