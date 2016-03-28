<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Oro\Bundle\ApiBundle\Config\AbstractConfigLoader;

class TestConfigLoader extends AbstractConfigLoader
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $result = new TestConfig();
        foreach ($config as $key => $value) {
            $this->loadConfigValue($result, $key, $value);
        }

        return $result;
    }
}
