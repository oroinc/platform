<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\DependencyInjection\OroCacheExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroCacheExtensionTest extends ExtensionTestCase
{
    private $extension;

    protected function setUp()
    {
        $this->extension = new OroCacheExtension();
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_cache.action.handler.invalidate_scheduled',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
