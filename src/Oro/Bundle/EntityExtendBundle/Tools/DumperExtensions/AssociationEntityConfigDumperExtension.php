<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

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
