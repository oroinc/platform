<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

abstract class AbstractAssociationEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var AssociationBuilder */
    protected $associationBuilder;

    /** @var ConfigInterface[] */
    private $targetEntityConfigs;

    /**
     * @param ConfigManager      $configManager
     * @param AssociationBuilder $associationBuilder
     */
    public function __construct(
        ConfigManager $configManager,
        AssociationBuilder $associationBuilder
    ) {
        $this->configManager      = $configManager;
        $this->associationBuilder = $associationBuilder;
    }

    /**
     * Gets the kind of the association. For example 'activity', 'sponsorship' etc
     *
     * @return string|null The association kind or NULL for unclassified (default) association
     */
    protected function getAssociationKind()
    {
        return null;
    }

    /**
     * Gets the type of the association. For example manyToOne or manyToMany
     * {@see Oro\Bundle\EntityExtendBundle\Extend\RelationType}
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
            case RelationType::MANY_TO_ONE:
                $this->associationBuilder->createManyToOneAssociation(
                    $sourceEntityClass,
                    $targetEntityClass,
                    $this->getAssociationKind()
                );
                break;
            case RelationType::MANY_TO_MANY:
                $this->associationBuilder->createManyToManyAssociation(
                    $sourceEntityClass,
                    $targetEntityClass,
                    $this->getAssociationKind()
                );
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

            $configs = $this->configManager->getProvider($this->getAssociationScope())->getConfigs();
            foreach ($configs as $config) {
                if ($this->isTargetEntityApplicable($config)) {
                    $this->targetEntityConfigs[] = $config;
                }
            }
        }

        return $this->targetEntityConfigs;
    }
}
