<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimdJsonPolyfill\JsonDecoder;

final class JsonDecoderTest extends TestCase
{
    public function testDecodeSimpleJson(): void
    {
        $json = '{"message":"Hello World"}';
        $result = JsonDecoder::decode($json, true);

        $this->assertIsArray($result);
        $this->assertSame('Hello World', $result['message']);
    }

    public function testDecodeReturnsObject(): void
    {
        $json = '{"status":"success","code":200}';
        $result = JsonDecoder::decode($json, false);

        $this->assertIsObject($result);
        $this->assertSame('success', $result->status);
        $this->assertSame(200, $result->code);
    }

    public function testDecodeWithDefaultAssociative(): void
    {
        $json = '{"key":"value"}';
        $result = JsonDecoder::decode($json);

        // Default behavior returns object
        $this->assertIsObject($result);
        $this->assertSame('value', $result->key);
    }

    public function testIsSimdJsonAvailable(): void
    {
        $available = JsonDecoder::isSimdJsonAvailable();
        $this->assertIsBool($available);
    }

    public function testDecodeComplexNestedStructure(): void
    {
        $json = '{"users":[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}],"total":2}';
        $result = JsonDecoder::decode($json, true);

        $this->assertIsArray($result);
        $this->assertCount(2, $result['users']);
        $this->assertSame('Alice', $result['users'][0]['name']);
        $this->assertSame('Bob', $result['users'][1]['name']);
        $this->assertSame(2, $result['total']);
    }

    public function testDecodeWithJsonThrowOnError(): void
    {
        $this->expectException(\JsonException::class);

        $invalidJson = '{"broken": json}';
        JsonDecoder::decode($invalidJson, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testDecodeInvalidJsonWithoutFlag(): void
    {
        $invalidJson = '{"invalid"}';
        $result = JsonDecoder::decode($invalidJson, true);

        $this->assertNull($result);
    }
}
