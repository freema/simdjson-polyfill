<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use SimdJsonPolyfill\Strategy\PolyfillStrategy;

final class PolyfillStrategyTest extends TestCase
{
    private PolyfillStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new PolyfillStrategy();
    }

    public function testIsAlwaysAvailable(): void
    {
        $this->assertTrue($this->strategy->isAvailable());
    }

    public function testGetName(): void
    {
        $this->assertSame('polyfill', $this->strategy->getName());
    }

    public function testGetPriority(): void
    {
        $this->assertSame(10, $this->strategy->getPriority());
    }

    public function testEnableDoesNotThrow(): void
    {
        $this->strategy->enable();
        $this->addToAssertionCount(1);
    }

    public function testDecodeSimpleJson(): void
    {
        $json = '{"name":"John","age":30}';
        $result = $this->strategy->decode($json, true);

        $this->assertIsArray($result);
        $this->assertSame('John', $result['name']);
        $this->assertSame(30, $result['age']);
    }

    public function testDecodeReturnsObject(): void
    {
        $json = '{"name":"John","age":30}';
        $result = $this->strategy->decode($json, false);

        $this->assertIsObject($result);
        $this->assertSame('John', $result->name);
        $this->assertSame(30, $result->age);
    }

    public function testDecodeWithDepth(): void
    {
        $json = '{"level1":{"level2":{"level3":"value"}}}';
        $result = $this->strategy->decode($json, true, 512);

        $this->assertIsArray($result);
        $this->assertSame('value', $result['level1']['level2']['level3']);
    }

    public function testDecodeInvalidJsonReturnsNull(): void
    {
        $json = '{invalid json}';
        $result = $this->strategy->decode($json, true);

        $this->assertNull($result);
    }

    public function testDecodeInvalidJsonThrowsWithFlag(): void
    {
        $this->expectException(\JsonException::class);

        $json = '{invalid json}';
        $this->strategy->decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testDecodeEmptyArray(): void
    {
        $json = '[]';
        $result = $this->strategy->decode($json, true);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testDecodeEmptyObject(): void
    {
        $json = '{}';
        $result = $this->strategy->decode($json, true);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testDecodeNestedArray(): void
    {
        $json = '[1, 2, [3, 4, [5, 6]]]';
        $result = $this->strategy->decode($json, true);

        $this->assertIsArray($result);
        $this->assertSame([1, 2, [3, 4, [5, 6]]], $result);
    }

    public function testDecodeUnicodeString(): void
    {
        $json = '{"text":"Hello 🚀 World"}';
        $result = $this->strategy->decode($json, true);

        $this->assertIsArray($result);
        $this->assertSame('Hello 🚀 World', $result['text']);
    }

    public function testDecodeNull(): void
    {
        $json = 'null';
        $result = $this->strategy->decode($json, true);

        $this->assertNull($result);
    }

    public function testDecodeBoolean(): void
    {
        $json = 'true';
        $result = $this->strategy->decode($json, true);

        $this->assertTrue($result);
    }

    public function testDecodeNumber(): void
    {
        $json = '42.5';
        $result = $this->strategy->decode($json, true);

        $this->assertSame(42.5, $result);
    }
}
