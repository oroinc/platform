<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

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
        return 'manyToOne';
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
