<?php

namespace Tests\Unit;

use App\Models\MockEndpoint;
use PHPUnit\Framework\TestCase;

class MockEndpointTest extends TestCase
{
    public function testMatchesPathExact(): void
    {
        $m = new MockEndpoint();
        $m->path = '/api/users';
        $this->assertTrue($m->matchesPath('/api/users'));
        $this->assertFalse($m->matchesPath('/api/users/1'));
    }

    public function testMatchesPathWithParams(): void
    {
        $m = new MockEndpoint();
        $m->path = '/api/users/{id}/posts/{postId}';
        $this->assertTrue($m->matchesPath('/api/users/42/posts/7'));
        $this->assertFalse($m->matchesPath('/api/users/42/posts'));
    }

    public function testExtractPathParams(): void
    {
        $m = new MockEndpoint();
        $m->path = '/api/users/{id}/posts/{postId}';
        $params = $m->extractPathParams('/api/users/42/posts/7');
        $this->assertSame(['id' => '42', 'postId' => '7'], $params);

        $m->path = '/api/users';
        $this->assertSame([], $m->extractPathParams('/api/users'));
    }
}
