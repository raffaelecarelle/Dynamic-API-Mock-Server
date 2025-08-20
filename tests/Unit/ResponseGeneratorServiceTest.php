<?php

namespace Tests\Unit;

use App\Services\ResponseGeneratorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ResponseGeneratorServiceTest extends TestCase
{
    private function service(): ResponseGeneratorService
    {
        return new ResponseGeneratorService(new NullLogger());
    }

    public function testGenerateResponseStatic(): void
    {
        $svc = $this->service();
        $mock = [
            'response_body' => ['ok' => true],
            'headers' => ['X-Test' => '1'],
            'status_code' => 201,
            'is_dynamic' => false,
        ];
        $res = $svc->generateResponse($mock, []);
        $this->assertSame(['ok' => true], $res['body']);
        $this->assertSame(['X-Test' => '1'], $res['headers']);
        $this->assertSame(201, $res['status_code']);
    }

    public function testGenerateResponseDynamicWithRequestParam(): void
    {
        $svc = $this->service();
        $mock = [
            'response_body' => ['echo' => null],
            'headers' => [],
            'status_code' => 200,
            'is_dynamic' => true,
            'dynamic_rules' => [
                [
                    'type' => 'request_param',
                    'target' => 'echo',
                    'param_type' => 'query',
                    'param_name' => 'q',
                    'default' => 'none'
                ]
            ]
        ];
        $req = [
            'path_params' => [],
            'query_params' => ['q' => 'hello'],
            'body' => []
        ];
        $res = $svc->generateResponse($mock, $req);
        $this->assertSame('hello', $res['body']['echo']);
    }

    public function testConditionalRule(): void
    {
        $svc = $this->service();
        $mock = [
            'response_body' => ['result' => null],
            'is_dynamic' => true,
            'dynamic_rules' => [
                [
                    'type' => 'conditional',
                    'target' => 'result',
                    'condition' => [
                        'param_type' => 'path',
                        'param_name' => 'id',
                        'operator' => 'equals',
                        'value' => '42'
                    ],
                    'true_value' => 'answer',
                    'false_value' => 'unknown'
                ]
            ]
        ];
        $req = [
            'path_params' => ['id' => '42'],
            'query_params' => [],
            'body' => []
        ];
        $res = $svc->generateResponse($mock, $req);
        $this->assertSame('answer', $res['body']['result']);

        $req['path_params']['id'] = '7';
        $res = $svc->generateResponse($mock, $req);
        $this->assertSame('unknown', $res['body']['result']);
    }

    public function testSetAndGetValueAtPath(): void
    {
        $svc = $this->service();
        $ref = new \ReflectionClass(ResponseGeneratorService::class);
        $set = $ref->getMethod('setValueAtPath');
        $set->setAccessible(true);
        $get = $ref->getMethod('getValueFromPath');
        $get->setAccessible(true);

        $data = ['a' => ['b' => ['c' => 1]]];
        $data = $set->invoke($svc, $data, 'a.b.d', 2);
        $this->assertSame(2, $get->invoke($svc, $data, 'a.b.d'));
        $this->assertSame(1, $get->invoke($svc, $data, 'a.b.c'));
    }
}