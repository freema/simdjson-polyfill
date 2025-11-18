<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Bridge\Symfony;

use SimdJsonPolyfill\Bridge\Symfony\DependencyInjection\SimdJsonExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony Bundle for SimdJsonPolyfill integration.
 */
final class SimdJsonBundle extends Bundle
{
    public function getContainerExtension(): SimdJsonExtension
    {
        return new SimdJsonExtension();
    }
}
