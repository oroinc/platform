<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Bundle\ActionBundle\Exception\ActionNotFoundException;
use Oro\Bundle\ActionBundle\Model\Assembler\ActionGroupAssembler;

class ActionGroupRegistry
{
    /** @var ConfigurationProviderInterface */
    protected $configurationProvider;

    /** @var ActionGroupAssembler */
    protected $assembler;

    /** @var array|ActionGroup[] */
    protected $actionGroups;

    /**
     * @param ConfigurationProviderInterface $configurationProvider
     * @param ActionGroupAssembler $assembler
     */
    public function __construct(ConfigurationProviderInterface $configurationProvider, ActionGroupAssembler $assembler)
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

    /**
     * @param $name
     * @return ActionGroup
     * @throws ActionNotFoundException
     */
    public function get($name)
    {
        $this->loadActionGroups();

        if (array_key_exists($name, $this->actionGroups)) {
            return $this->actionGroups[$name];
        } else {
            throw new ActionNotFoundException($name);
        }
    }

    protected function loadActionGroups()
    {
        if ($this->actionGroups !== null) {
            return;
        }

        $this->actionGroups = [];

        $configuration = $this->configurationProvider->getConfiguration();
        $actionGroups = $this->assembler->assemble($configuration);

        foreach ($actionGroups as $actionGroup) {
            $this->actionGroups[$actionGroup->getDefinition()->getName()] = $actionGroup;
        }
    }
}
