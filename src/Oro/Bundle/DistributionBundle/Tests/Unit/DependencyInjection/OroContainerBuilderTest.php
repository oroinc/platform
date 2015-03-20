<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DistributionBundle\DependencyInjection\OroContainerBuilder;

class OroContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    const EXTENSION = 'ext';

    private $builder;

    public function setUp()
    {
        $extension = $this->getMock('Symfony\Component\DependencyInjection\Extension\ExtensionInterface');
        $extension
            ->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue(static::EXTENSION));

        $this->builder = new OroContainerBuilder();
        $this->builder->registerExtension($extension);
    }

    public function testSetExtensionConfigShouldOverwriteCurrentConfig()
    {
        $originalConfig = ['prop' => 'val'];
        $overwrittenConfig = [['p' => 'v']];

        $this->builder->prependExtensionConfig(static::EXTENSION, $originalConfig);
        $this->assertEquals([$originalConfig], $this->builder->getExtensionConfig(static::EXTENSION));

        $this->builder->setExtensionConfig(static::EXTENSION, $overwrittenConfig);
        $this->assertEquals($overwrittenConfig, $this->builder->getExtensionConfig(static::EXTENSION));
    }
}
