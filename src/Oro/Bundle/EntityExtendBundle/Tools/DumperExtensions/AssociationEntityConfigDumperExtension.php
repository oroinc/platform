<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Provides common functionality for dumper extensions that handle many-to-one associations.
 *
 * This class extends the abstract association dumper extension to specifically handle many-to-one relationships
 * where a single association entity is the owning side.
 * Subclasses must define which entity class owns the association.
 */
abstract class AssociationEntityConfigDumperExtension extends AbstractAssociationEntityConfigDumperExtension
{
    /**
     * Gets the entity class who is owning side of the association
     *
     * @return string
     */
    abstract protected function getAssociationEntityClass();

    #[\Override]
    protected function getAssociationType()
    {
        return RelationType::MANY_TO_ONE;
    }

    #[\Override]
    protected function getAssociationAttributeName()
    {
        return 'enabled';
    }

    #[\Override]
    protected function isTargetEntityApplicable(ConfigInterface $targetEntityConfig)
    {
        return $targetEntityConfig->is($this->getAssociationAttributeName());
    }

    #[\Override]
    public function preUpdate()
    {
        $targetEntityConfigs = $this->getTargetEntityConfigs();
        $entityClass         = $this->getAssociationEntityClass();
        foreach ($targetEntityConfigs as $targetEntityConfig) {
            $this->createAssociation($entityClass, $targetEntityConfig->getId()->getClassName());
        }
    }
}
