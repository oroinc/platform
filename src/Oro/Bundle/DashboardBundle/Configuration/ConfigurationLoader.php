<?php

namespace Oro\Bundle\DashboardBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\AbstractConfigurationProvider;

class ConfigurationLoader extends AbstractConfigurationProvider
{
    const NODE_CONFIG = 'oro_dashboard_config';
    const NODE_DASHBOARD = 'dashboards';
    const NODE_WIDGET = 'widgets';

    /**
     * @var string
     */
    protected $configFilePattern = 'dashboard.yml';

    /**
     * @var mixed
     */
    protected $dashboards = [];

    /**
     * @var mixed
     */
    protected $configData;

    /**
     * @param array $usedDirectories
     *
     * @return array
     */
    public function getDashboardConfiguration(array $usedDirectories = null)
    {
        $finder = $this->getConfigFinder((array)$usedDirectories);

        /** @var $file \SplFileInfo */
        foreach ($finder as $file) {
            $this->configData[] = $this->loadConfigFile($file);
        }

        foreach ($this->configData as $configData) {
            if (isset($configData[self::NODE_CONFIG][self::NODE_DASHBOARD])) {
                foreach ($configData[self::NODE_CONFIG][self::NODE_DASHBOARD] as $dashboardName => $dashboard) {
                    if (!isset($this->dashboards[$dashboardName])) {
                        $this->dashboards[$dashboardName] = $dashboard;

                        continue;
                    }

                    $this->dashboards[$dashboardName] = array_replace_recursive(
                        $this->dashboards[$dashboardName],
                        $dashboard
                    );
                }
            }
        }

        foreach ($this->configData as $configData) {
            if (isset($configData[self::NODE_CONFIG][self::NODE_WIDGET])) {
                foreach ($configData[self::NODE_CONFIG][self::NODE_WIDGET] as $widgetName => $widget) {
                    foreach ($this->dashboards as $dashboardName => $dashboard) {
                        if (isset($dashboard[self::NODE_WIDGET][$widgetName])) {
                            $this->dashboards[$dashboardName][self::NODE_WIDGET][$widgetName] = array_replace_recursive(
                                $this->dashboards[$dashboardName][self::NODE_WIDGET][$widgetName],
                                $widget ?: []
                            );
                        }
                    }
                }
            }
        }

        return $this->dashboards;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigFilePattern()
    {
        return $this->configFilePattern;
    }
}
