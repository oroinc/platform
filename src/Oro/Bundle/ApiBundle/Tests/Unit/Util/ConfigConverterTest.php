<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Util\ConfigConverter;

class ConfigConverterTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertConfigWithoutParentResourceClass()
    {
        $config = [
            'exclusion_policy' => 'all'
        ];

        $configConverter = new ConfigConverter();
        $convertedConfig = $configConverter->convertConfig($config);

        self::assertFalse($convertedConfig->has('skip_acl_for_root_entity'));
    }

    public function testConvertConfigWithParentResourceClass()
    {
        $config = [
            'exclusion_policy'      => 'all',
            'parent_resource_class' => 'Test\Entity'
        ];

        $configConverter = new ConfigConverter();
        $convertedConfig = $configConverter->convertConfig($config);

        self::assertTrue($convertedConfig->has('skip_acl_for_root_entity'));
        self::assertTrue($convertedConfig->get('skip_acl_for_root_entity'));
    }
}
