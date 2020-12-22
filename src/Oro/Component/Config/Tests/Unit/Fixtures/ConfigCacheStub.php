<?php

namespace Oro\Component\Config\Tests\Unit\Fixtures;

use Oro\Component\Config\Cache\ConfigCache;

class ConfigCacheStub extends ConfigCache
{
    public function doEnsureDependenciesWarmedUp(): void
    {
        $this->ensureDependenciesWarmedUp();
    }
}
