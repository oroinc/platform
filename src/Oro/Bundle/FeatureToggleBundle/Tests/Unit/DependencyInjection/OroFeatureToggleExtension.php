<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\OroFeatureToggleExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroFeatureToggleExtensionTest extends ExtensionTestCase
{
    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroFeatureToggleExtension();

        $this->assertEquals(OroFeatureToggleExtension::ALIAS, $extension->getAlias());
    }
}
