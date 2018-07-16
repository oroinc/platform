<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\ConfigFilter;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ConfigFilter\ByOriginFilter;

class ByOriginFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider applyDataProvider
     */
    public function testApply(ConfigInterface $config, $expectedResult)
    {
        $filter = new ByOriginFilter([ExtendScope::ORIGIN_CUSTOM]);
        $this->assertEquals($expectedResult, call_user_func($filter, $config));
    }

    public function applyDataProvider()
    {
        return [
            [
                $this->getEntityConfig(
                    'Test\Entity',
                    ['state' => ExtendScope::STATE_ACTIVE, 'origin' => ExtendScope::ORIGIN_CUSTOM]
                ),
                true
            ],
            [
                $this->getEntityConfig(
                    'Test\Entity',
                    ['state' => ExtendScope::STATE_UPDATE, 'origin' => ExtendScope::ORIGIN_CUSTOM]
                ),
                false
            ],
            [
                $this->getEntityConfig(
                    'Test\Entity',
                    ['state' => ExtendScope::STATE_UPDATE, 'origin' => ExtendScope::ORIGIN_SYSTEM]
                ),
                true
            ],
        ];
    }

    /**
     * @param string $className
     * @param mixed  $values
     *
     * @return ConfigInterface
     */
    protected function getEntityConfig($className, $values)
    {
        $configId = new EntityConfigId('extend', $className);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }
}
