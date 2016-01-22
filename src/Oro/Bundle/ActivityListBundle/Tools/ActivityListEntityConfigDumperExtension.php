<?php

namespace Oro\Bundle\ActivityListBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;

class ActivityListEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    const ASSOCIATION_KIND = 'activityList';
    const ENTITY_CLASS     = 'Oro\Bundle\ActivityListBundle\Entity\ActivityList';

    protected $targetEntityConfigs;

    /** @var ConfigManager */
    protected $configManager;

    /** @var ActivityListChainProvider */
    protected $listProvider;

    /** @var AssociationBuilder */
    protected $associationBuilder;

    /**
     * @param ActivityListChainProvider $listProvider
     * @param ConfigManager             $configManager
     * @param AssociationBuilder        $associationBuilder
     */
    public function __construct(
        ActivityListChainProvider $listProvider,
        ConfigManager $configManager,
        AssociationBuilder $associationBuilder
    ) {
        $this->listProvider       = $listProvider;
        $this->configManager      = $configManager;
        $this->associationBuilder = $associationBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        if ($actionType === ExtendConfigDumper::ACTION_PRE_UPDATE) {
            $targetEntityConfigs = $this->getTargetEntityConfigs();

            return !empty($targetEntityConfigs)
                && $this->configManager->getProvider('extend')->hasConfig(self::ENTITY_CLASS);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $targetEntityConfigs = $this->getTargetEntityConfigs();
        foreach ($targetEntityConfigs as $targetEntityConfig) {
            $this->associationBuilder->createManyToManyAssociation(
                self::ENTITY_CLASS,
                $targetEntityConfig->getId()->getClassName(),
                self::ASSOCIATION_KIND
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
            $targetEntityClasses       = $this->listProvider->getTargetEntityClasses(false);
            $this->targetEntityConfigs = [];

            $configs = $this->configManager->getProvider('extend')->getConfigs();
            foreach ($configs as $config) {
                if ($config->is('upgradeable')
                    && in_array($config->getId()->getClassName(), $targetEntityClasses)
                ) {
                    $this->targetEntityConfigs[] = $config;
                }
            }
        }

        return $this->targetEntityConfigs;
    }
}
