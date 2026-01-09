<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Provides common functionality for dumper extensions that handle many-to-many associations.
 *
 * This class extends the abstract association dumper extension to specifically handle many-to-many relationships
 * where multiple entity classes can be associated.
 * It processes configurations where the association attribute contains a collection of entity classes.
 */
abstract class MultipleAssociationEntityConfigDumperExtension extends AbstractAssociationEntityConfigDumperExtension
{
    #[\Override]
    protected function getAssociationType()
    {
        return RelationType::MANY_TO_MANY;
    }

    #[\Override]
    protected function isTargetEntityApplicable(ConfigInterface $targetEntityConfig)
    {
        $entityClasses = $targetEntityConfig->get($this->getAssociationAttributeName());

        return !empty($entityClasses);
    }

    #[\Override]
    public function preUpdate()
    {
        $targetEntityConfigs = $this->getTargetEntityConfigs();
        foreach ($targetEntityConfigs as $targetEntityConfig) {
            $entityClasses = $targetEntityConfig->get($this->getAssociationAttributeName());
            if (!empty($entityClasses)) {
                $targetEntityClass = $targetEntityConfig->getId()->getClassName();
                foreach ($entityClasses as $entityClass) {
                    $this->createAssociation($entityClass, $targetEntityClass);
                }
            }
        }
    }
}
