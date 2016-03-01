<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\ConfigFilter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ByInitialStateFilter extends AbstractFilter
{
    /**
     * @var array
     *  [
     *      'entities' => [{class name} => {state}, ...],
     *      'fields'   => [{class name} => [{field name} => {state}, ...], ...]
     *  ]
     */
    protected $initialStates;

    /**
     * @param array $initialStates
     */
    public function __construct(array $initialStates)
    {
        $this->initialStates = $initialStates;
    }

    /**
     * {@inheritdoc}
     */
    protected function apply(ConfigInterface $config)
    {
        $configId  = $config->getId();
        $className = $configId->getClassName();
        if ($configId instanceof FieldConfigId) {
            $fieldName = $configId->getFieldName();
            if (!isset($this->initialStates['fields'][$className][$fieldName])) {
                return true;
            }
            $initialState = $this->initialStates['fields'][$className][$fieldName];
        } else {
            if (!isset($this->initialStates['entities'][$className])) {
                return true;
            }
            $initialState = $this->initialStates['entities'][$className];
        }
        if ($initialState === ExtendScope::STATE_ACTIVE) {
            return true;
        }
        if ($initialState === ExtendScope::STATE_DELETE && !$config->is('state', ExtendScope::STATE_DELETE)) {
            return true;
        }

        return false;
    }
}
