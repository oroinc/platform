<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Oro\Bundle\ApiBundle\Config\AbstractConfigLoader;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderInterface;

class TestConfigLoader extends AbstractConfigLoader implements ConfigLoaderInterface
{
    /** @var array */
    protected $methodMap = [
        TestConfig::EXCLUSION_POLICY => 'setExclusionPolicy',
        TestConfig::LABEL            => 'setLabel',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $result = new TestConfig();

        foreach ($config as $key => $value) {
            if (isset($this->methodMap[$key])) {
                $this->callSetter($result, $this->methodMap[$key], $value);
            } else {
                $this->setValue($result, $key, $value);
            }
        }

        return $result;
    }
}
