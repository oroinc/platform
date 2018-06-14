<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The loader for "status_codes" configuration section.
 */
class StatusCodesConfigLoader extends AbstractConfigLoader
{
    private const METHOD_MAP = [
        ConfigUtil::EXCLUDE => 'setExcluded'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $codes = new StatusCodesConfig();
        foreach ($config as $key => $value) {
            $codes->addCode($key, $this->loadCode($value));
        }

        return $codes;
    }

    /**
     * @param array|null $config
     *
     * @return StatusCodeConfig
     */
    protected function loadCode(array $config = null)
    {
        $code = new StatusCodeConfig();
        if (!empty($config)) {
            foreach ($config as $key => $value) {
                $this->loadConfigValue($code, $key, $value, self::METHOD_MAP);
            }
        }

        return $code;
    }
}
