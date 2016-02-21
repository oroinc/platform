<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Oro\Bundle\ApiBundle\Config\AbstractConfigLoader;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class TestConfigLoader extends AbstractConfigLoader implements ConfigLoaderInterface
{
    /** @var array */
    protected $methodMap = [
        ConfigUtil::EXCLUSION_POLICY => 'setExclusionPolicy',
        ConfigUtil::LABEL            => 'setLabel',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $sorters = new TestConfig();

        foreach ($config as $key => $value) {
            if (isset($this->methodMap[$key])) {
                $this->callSetter($sorters, $this->methodMap[$key], $value);
            } else {
                $this->setValue($sorters, $key, $value);
            }
        }

        return $sorters;
    }
}
