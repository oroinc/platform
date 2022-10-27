<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Util\ConfigNormalizer;

class ConfigNormalizerTest extends ConfigNormalizerTestCase
{
    /**
     * @dataProvider normalizeConfigProvider
     */
    public function testNormalizeConfig(array $config, array $expectedConfig)
    {
        $normalizer = new ConfigNormalizer();

        $normalizedConfig = $normalizer->normalizeConfig($config);

        self::assertEquals($expectedConfig, $normalizedConfig);
    }
}
