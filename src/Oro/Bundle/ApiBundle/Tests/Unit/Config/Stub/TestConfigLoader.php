<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Oro\Bundle\ApiBundle\Config\Loader\AbstractConfigLoader;

class TestConfigLoader extends AbstractConfigLoader
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config): mixed
    {
        $result = new TestConfig();
        foreach ($config as $key => $value) {
            $this->loadConfigValue($result, $key, $value);
        }

        return $result;
    }
}
