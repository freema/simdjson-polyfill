<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimdJsonPolyfill\SimdJsonPolyfill;
use SimdJsonPolyfill\Strategy\PolyfillStrategy;

final class SimdJsonPolyfillTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset static state between tests
        $reflection = new \ReflectionClass(SimdJsonPolyfill::class);
        $property = $reflection->getProperty('activeStrategy');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testEnableWithAutoDetect(): void
    {
        SimdJsonPolyfill::enable(['auto_detect' => true]);
        $strategy = SimdJsonPolyfill::getActiveStrategy();

        $this->assertNotNull($strategy);
        $this->assertInstanceOf(\SimdJsonPolyfill\Strategy\StrategyInterface::class, $strategy);
    }

    public function testEnableWithExplicitPolyfillStrategy(): void
    {
        SimdJsonPolyfill::enable(['strategy' => 'polyfill']);
        $strategy = SimdJsonPolyfill::getActiveStrategy();

        $this->assertNotNull($strategy);
        $this->assertInstanceOf(PolyfillStrategy::class, $strategy);
    }

    public function testEnableThrowsForUnavailableStrategy(): void
    {
        // Skip if UOPZ is actually available (which is now the case in Docker)
        if (extension_loaded('uopz') && extension_loaded('simdjson')) {
            $this->markTestSkipped('UOPZ and simdjson are available, cannot test unavailable strategy');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not available');

        // UOPZ strategy requires ext-uopz which might not be installed
        SimdJsonPolyfill::enable(['strategy' => 'uopz']);
    }

    public function testEnableThrowsWhenNoStrategyAndAutoDetectDisabled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No strategy specified');

        SimdJsonPolyfill::enable(['auto_detect' => false]);
    }

    public function testDecodeAutoEnablesIfNotEnabled(): void
    {
        $json = '{"test":true}';
        $result = SimdJsonPolyfill::decode($json, true);

        $this->assertIsArray($result);
        $this->assertTrue($result['test']);
    }

    public function testDecodeUsesActiveStrategy(): void
    {
        SimdJsonPolyfill::enable(['strategy' => 'polyfill']);

        $json = '{"name":"Test","value":123}';
        $result = SimdJsonPolyfill::decode($json, true);

        $this->assertIsArray($result);
        $this->assertSame('Test', $result['name']);
        $this->assertSame(123, $result['value']);
    }

    public function testIsSimdJsonAvailable(): void
    {
        $available = SimdJsonPolyfill::isSimdJsonAvailable();
        $this->assertIsBool($available);
        $this->assertSame(extension_loaded('simdjson'), $available);
    }

    public function testGetStrategyInfo(): void
    {
        $info = SimdJsonPolyfill::getStrategyInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('polyfill', $info);
        $this->assertArrayHasKey('uopz', $info);
        $this->assertArrayHasKey('namespace', $info);

        foreach ($info as $strategyInfo) {
            $this->assertArrayHasKey('name', $strategyInfo);
            $this->assertArrayHasKey('available', $strategyInfo);
            $this->assertArrayHasKey('priority', $strategyInfo);
            $this->assertIsString($strategyInfo['name']);
            $this->assertIsBool($strategyInfo['available']);
            $this->assertIsInt($strategyInfo['priority']);
        }
    }

    public function testGetStrategyInfoShowsPolyfillAsAvailable(): void
    {
        $info = SimdJsonPolyfill::getStrategyInfo();

        $this->assertTrue($info['polyfill']['available']);
        $this->assertSame('polyfill', $info['polyfill']['name']);
        $this->assertSame(10, $info['polyfill']['priority']);
    }

    public function testEnableThrowsForUnknownStrategy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown strategy');

        SimdJsonPolyfill::enable(['strategy' => 'nonexistent']);
    }
}
