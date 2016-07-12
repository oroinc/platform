<?php

namespace Oro\Bundle\RequireJSBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\RequireJSBundle\Config\Config as RequireJSConfig;

use Oro\Component\PhpUtils\ArrayUtil;

class ConfigProvider extends AbstractConfigProvider
{
    const REQUIREJS_CONFIG_CACHE_KEY    = 'requirejs_config';
    const REQUIREJS_CONFIG_FILE         = 'js/require-config.js';

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $configs = $this->getConfigs();

        return current($configs);
    }

    /**
     * {@inheritdoc}
     */
    public function collectConfigs()
    {
        return [
            $this->createRequireJSConfig(self::REQUIREJS_CONFIG_FILE, $this->config['build_path'])
        ];
    }

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return self::REQUIREJS_CONFIG_CACHE_KEY;
    }
}
