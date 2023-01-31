<?php

namespace Oro\Bundle\ApiBundle\Config\Loader;

use Oro\Bundle\ApiBundle\Config\StatusCodeConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The loader for "status_codes" configuration section.
 */
class StatusCodesConfigLoader extends AbstractConfigLoader
{
    private const METHOD_MAP = [
        ConfigUtil::DESCRIPTION => 'setDescription',
        ConfigUtil::EXCLUDE     => 'setExcluded'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config): mixed
    {
        $codes = new StatusCodesConfig();
        foreach ($config as $key => $value) {
            $codes->addCode($key, $this->loadCode($value));
        }

        return $codes;
    }

    private function loadCode(array $config = null): StatusCodeConfig
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
