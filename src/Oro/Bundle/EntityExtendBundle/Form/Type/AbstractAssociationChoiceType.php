<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

/**
 * The abstract class for form types are used to work with entity config attributes
 * related to an association selector.
 */
abstract class AbstractAssociationChoiceType extends AbstractConfigType
{
    /**
     * {@inheritdoc}
     */
    protected function isReadOnly($options)
    {
        /** @var EntityConfigId $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if (!empty($className)) {
            // disable for dictionary entities
            $groupingConfigProvider = $this->configManager->getProvider('grouping');
            if ($groupingConfigProvider->hasConfig($className)) {
                $groups = $groupingConfigProvider->getConfig($className)->get('groups');
                if (!empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups)) {
                    return true;
                }
            }
        }

        return parent::isReadOnly($options);
    }
}
