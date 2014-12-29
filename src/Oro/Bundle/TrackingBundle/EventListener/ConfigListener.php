<?php

namespace Oro\Bundle\TrackingBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Symfony\Component\Routing\Router;

class ConfigListener
{
    /**
     * @var string
     */
    protected $dynamicTrackingRouteName = 'oro_tracking_data_create';

    /**
     * @var string
     */
    protected $prefix = 'oro_tracking';

    /**
     * @var array
     */
    protected $keys = array(
        'dynamic_tracking_enabled',
        'log_rotate_interval',
        'piwik_host',
        'piwik_token_auth'
    );

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var string
     */
    protected $logsDir;

    /**
     * @param ConfigManager $configManager
     * @param Router $router
     * @param string $logsDir
     */
    public function __construct(
        ConfigManager $configManager,
        Router $router,
        $logsDir
    ) {
        $this->configManager = $configManager;
        $this->router = $router;
        $this->logsDir = $logsDir;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        $changedData = array();
        foreach ($this->keys as $key) {
            $configKey = $this->getKeyName($key);
            if ($event->isChanged($configKey)) {
                $changedData[$key] = $event->getNewValue($configKey);
            }
        }

        if ($changedData) {
            $this->updateTrackingConfig($changedData);
        }
    }

    /**
     * @param array $configuration
     */
    protected function updateTrackingConfig(array $configuration)
    {
        foreach ($this->keys as $key) {
            if (!array_key_exists($key, $configuration)) {
                $value = $this->configManager->get($this->getKeyName($key));
                $value = is_array($value) ? $value['value'] : $value;
                $configuration[$key] = $value;
            }
        }

        if (!empty($configuration['dynamic_tracking_enabled'])) {
            $configuration['dynamic_tracking_endpoint'] = $this->router->generate($this->dynamicTrackingRouteName);
        } else {
            $configuration['dynamic_tracking_endpoint'] = null;
        }

        $trackingDir = $this->logsDir . DIRECTORY_SEPARATOR . 'tracking';
        if (!is_dir($trackingDir)) {
            mkdir($trackingDir);
        }

        $settingsFile = $trackingDir . DIRECTORY_SEPARATOR . 'settings.ser';
        file_put_contents($settingsFile, serialize($configuration));
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getKeyName($key)
    {
        return $this->prefix . '.' . $key;
    }
}
