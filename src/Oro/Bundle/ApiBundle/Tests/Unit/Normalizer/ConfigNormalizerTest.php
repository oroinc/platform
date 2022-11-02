<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Normalizer\ConfigNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Util\ConfigNormalizerTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ConfigNormalizerTest extends ConfigNormalizerTestCase
{
    private function updateExpectedConfig(array &$expectedConfig): void
    {
        if (array_key_exists(ConfigUtil::FIELDS, $expectedConfig)) {
            if (empty($expectedConfig[ConfigUtil::FIELDS])) {
                unset($expectedConfig[ConfigUtil::FIELDS]);
            } else {
                foreach ($expectedConfig[ConfigUtil::FIELDS] as &$field) {
                    if (null === $field) {
                        continue;
                    }
                    if (array_key_exists(ConfigUtil::EXCLUDE, $field) && false === $field[ConfigUtil::EXCLUDE]) {
                        unset($field[ConfigUtil::EXCLUDE]);
                    }
                    if (empty($field)) {
                        $field = null;
                    } else {
                        $this->updateExpectedConfig($field);
                    }
                }
            }
        }
    }

    /**
     * @dataProvider normalizeConfigProvider
     */
    public function testNormalizeConfig(array $config, array $expectedConfig)
    {
        $normalizer = new ConfigNormalizer();

        $configExtensionRegistry = new ConfigExtensionRegistry();
        $configLoaderFactory = new ConfigLoaderFactory($configExtensionRegistry);
        $configLoader = $configLoaderFactory->getLoader(ConfigUtil::DEFINITION);

        /** @var EntityDefinitionConfig $normalizedConfig */
        $normalizedConfig = $configLoader->load($config);
        $normalizer->normalizeConfig($normalizedConfig);

        $this->updateExpectedConfig($expectedConfig);
        self::assertEquals($expectedConfig, $normalizedConfig->toArray());
    }
}
