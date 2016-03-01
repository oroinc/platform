<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Tools\MixinConfigurationHelper;

class MixinListener
{
    const MIXINS = 'mixins';

    const GRID_MIXIN = 'grid-mixin';

    /** @var MixinConfigurationHelper */
    protected $mixinConfigurationHelper;

    /** @var array */
    protected $appliedFor = [];

    /** @param MixinConfigurationHelper $mixinConfigurationHelper */
    public function __construct(MixinConfigurationHelper $mixinConfigurationHelper)
    {
        $this->mixinConfigurationHelper = $mixinConfigurationHelper;
    }

    /**
     * @param DatagridConfiguration $config
     * @param ParameterBag          $parameters
     *
     * @return bool
     */
    public function isApplicable(DatagridConfiguration $config, ParameterBag $parameters)
    {
        return $parameters->get(self::GRID_MIXIN, false) || $config->offsetGetOr(self::MIXINS, false);
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $parameters = $event->getParameters();
        $config     = $event->getConfig();
        if (!$this->isApplicable($config, $parameters)) {
            return;
        }

        $gridName = $config->getName();
        $mixins   = $this->getMixins($config, $parameters);
        foreach ($mixins as $mixin) {
            if (empty($this->appliedFor[$gridName . $mixin])) {
                $this->mixinConfigurationHelper->extendConfiguration($config, $mixin);
                $this->appliedFor[$gridName . $mixin] = true;
            }
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param ParameterBag          $parameters
     *
     * @return string[]
     */
    protected function getMixins(DatagridConfiguration $config, ParameterBag $parameters)
    {
        $mixins = (array)$config->offsetGetOr(self::MIXINS, []);

        $mixins = array_merge($mixins, (array)$parameters->get(self::GRID_MIXIN, []));

        return $mixins;
    }
}
