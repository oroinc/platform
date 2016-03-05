<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;

class ActionGroupRegistry
{
    /** @var ActionConfigurationProvider */
    protected $configurationProvider;

    /** @var ActionGroupAssembler */
    protected $assembler;

    /** @var array|ActionGroup[] */
    protected $actionGroups;

    /**
     * @param ActionConfigurationProvider $configurationProvider
     * @param ActionGroupAssembler $assembler
     */
    public function __construct(ActionConfigurationProvider $configurationProvider, ActionGroupAssembler $assembler)
    {
        $this->configurationProvider = $configurationProvider;
        $this->assembler = $assembler;
    }

    /**
     * @param string $name
     * @return null|ActionGroup
     */
    public function findByName($name)
    {
        $this->loadActionGroups();

        return array_key_exists($name, $this->actionGroups) ? $this->actionGroups[$name] : null;
    }

    protected function loadActionGroups()
    {
        if ($this->actionGroups !== null) {
            return;
        }

        $this->actionGroups = [];

        $configuration = $this->configurationProvider->getActionConfiguration();
        $actionGroups = $this->assembler->assemble($configuration);

        foreach ($actionGroups as $actionGroup) {
            $this->actionGroups[$actionGroup->getDefinition()->getName()] = $actionGroup;
        }
    }
}
