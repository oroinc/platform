<?php

namespace Oro\Bundle\ApiBundle\Config;

class StatusCodesConfigLoader extends AbstractConfigLoader
{
    /** @var array */
    protected $codeMethodMap = [
        StatusCodeConfig::EXCLUDE => 'setExcluded',
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
                $this->loadConfigValue($code, $key, $value, $this->codeMethodMap);
            }
        }

        return $code;
    }
}
