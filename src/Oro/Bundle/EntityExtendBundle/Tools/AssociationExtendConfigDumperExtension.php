<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

abstract class AssociationExtendConfigDumperExtension extends ExtendConfigDumperExtension
{
    /** @var AssociationBuildHelper */
    protected $assocBuildHelper;

    /** @var string[] */
    private $targetEntityNames;

    public function __construct(AssociationBuildHelper $assocBuildHelper)
    {
        $this->assocBuildHelper = $assocBuildHelper;
    }

    /**
     * Gets the entity class who is owning side of the association
     *
     * @return string
     */
    abstract protected function getAssociationEntityClass();

    /**
     * Gets the scope name where the association is declared
     *
     * @return string
     */
    abstract protected function getAssociationScope();

    /**
     * Gets the config attribute name which indicates whether the association is enabled or not
     *
     * @return string
     */
    protected function getAssociationAttributeName()
    {
        return 'enabled';
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        if ($actionType === ExtendConfigDumper::ACTION_PRE_UPDATE) {
            $targetEntities = $this->getTargetEntities();

            return !empty($targetEntities);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(array &$extendConfigs)
    {
        $entityClass          = $this->getAssociationEntityClass();
        $targetEntities       = $this->getTargetEntities();

        foreach ($targetEntities as $targetEntityClass) {
            $this->assocBuildHelper->createManyToOneAssociation($entityClass, $targetEntityClass);
        }
    }

    /**
     * Gets the list of class names for entities which can the target of the association
     *
     * @return string[] the list of class names
     */
    protected function getTargetEntities()
    {
        if (null === $this->targetEntityNames) {
            $this->targetEntityNames = [];

            $configs = $this->assocBuildHelper->getScopeConfigs($this->getAssociationScope());
            foreach ($configs as $config) {
                if ($config->is($this->getAssociationAttributeName())) {
                    $this->targetEntityNames[] = $config->getId()->getClassName();
                }
            }
        }

        return $this->targetEntityNames;
    }
}
