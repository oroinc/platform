<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

abstract class AbstractAssociationEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var AssociationBuilder */
    protected $associationBuilder;

    /** @var ConfigInterface[] */
    private $targetEntityConfigs;

    /**
     * @param AssociationBuilder $associationBuilder
     */
    public function __construct(AssociationBuilder $associationBuilder)
    {
        $this->associationBuilder = $associationBuilder;
    }

    /**
     * Gets the type of the association. For example manyToOne or manyToMany
     *
     * @return string
     */
    abstract protected function getAssociationType();

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
    abstract protected function getAssociationAttributeName();

    /**
     * Determines whether the association for the given target entity should be created or not
     *
     * @param ConfigInterface $targetEntityConfig The config for the scope returned by getAssociationScope method
     *
     * @return bool
     */
    abstract protected function isTargetEntityApplicable(ConfigInterface $targetEntityConfig);

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        if ($actionType === ExtendConfigDumper::ACTION_PRE_UPDATE) {
            $targetEntityConfigs = $this->getTargetEntityConfigs();

            return !empty($targetEntityConfigs);
        }

        return false;
    }

    /**
     * Creates the association between the given entities
     *
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     *
     * @throws \RuntimeException If the association cannot be created
     */
    protected function createAssociation($sourceEntityClass, $targetEntityClass)
    {
        switch ($this->getAssociationType()) {
            case 'manyToOne':
                $this->associationBuilder->createManyToOneAssociation($sourceEntityClass, $targetEntityClass);
                break;
            case 'manyToMany':
                $this->associationBuilder->createManyToManyAssociation($sourceEntityClass, $targetEntityClass);
                break;
            default:
                throw new \RuntimeException(
                    sprintf('The "%s" association is not supported.', $this->getAssociationType())
                );
        }
    }

    /**
     * Gets the list of configs for entities which can be the target of the association
     *
     * @return ConfigInterface[]
     */
    protected function getTargetEntityConfigs()
    {
        if (null === $this->targetEntityConfigs) {
            $this->targetEntityConfigs = [];

            $configManager = $this->associationBuilder->getConfigManager();
            $configs       = $configManager->getProvider($this->getAssociationScope())->getConfigs();
            foreach ($configs as $config) {
                if ($this->isTargetEntityApplicable($config)) {
                    $this->targetEntityConfigs[] = $config;
                }
            }
        }

        return $this->targetEntityConfigs;
    }
}
