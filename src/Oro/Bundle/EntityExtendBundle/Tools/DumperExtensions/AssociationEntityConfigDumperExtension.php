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

    /**
     * {@inheritdoc}
     */
    protected function getAssociationType()
    {
        return RelationType::MANY_TO_ONE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationAttributeName()
    {
        return 'enabled';
    }

    /**
     * {@inheritdoc}
     */
    protected function isTargetEntityApplicable(ConfigInterface $targetEntityConfig)
    {
        return $targetEntityConfig->is($this->getAssociationAttributeName());
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $targetEntityConfigs = $this->getTargetEntityConfigs();
        $entityClass         = $this->getAssociationEntityClass();
        foreach ($targetEntityConfigs as $targetEntityConfig) {
            $this->createAssociation($entityClass, $targetEntityConfig->getId()->getClassName());
        }
    }
}
