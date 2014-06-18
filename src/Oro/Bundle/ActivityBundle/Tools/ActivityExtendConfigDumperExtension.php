<?php

namespace Oro\Bundle\ActivityBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationBuildHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumperExtension;

class ActivityExtendConfigDumperExtension extends ExtendConfigDumperExtension
{
    /** @var ConfigInterface[] */
    private $targetСonfigs;

    /**
     * @param AssociationBuildHelper $assocBuildHelper
     */
    public function __construct(AssociationBuildHelper $assocBuildHelper)
    {
        $this->assocBuildHelper = $assocBuildHelper;
    }

    /**
     * Check if entity config matched rule for target entity
     *
     * @param ConfigInterface $config
     *
     * @return bool
     */
    protected function targetEntityMatch(ConfigInterface $config)
    {
        $activityItemNames = $config->get('items', false, []);

        return !empty($activityItemNames);
    }

    /**
     * Return scope for association specific config
     *
     * @return string
     */
    protected function getAssociationScope()
    {
        return 'activity';
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        if ($actionType === ExtendConfigDumper::ACTION_PRE_UPDATE) {
            $targetConfigs = $this->getTargetEntitiesConfigs();

            return !empty($targetConfigs);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(array &$extendConfigs)
    {
        $targetEntityConfigs = $this->getTargetEntitiesConfigs();

        /** @var ConfigInterface $targetEntityConfig */
        foreach ($targetEntityConfigs as $targetEntityConfig) {
            $targetEntityName    = $targetEntityConfig->getId()->getClassName();
            $activityEntityNames = $this->getActivitiesFromConfig($targetEntityConfig);

            // create many-to-many unidirectional relation between/from activity to association entity:
            // e.g. contact, account, etc
            foreach ($activityEntityNames as $activityEntityName) {
                $this->assocBuildHelper->createManyToManyAssociation($activityEntityName, $targetEntityName);
            }
        }
    }

    /**
     * Gets the list of class names for entities which can the target of the association
     *
     * @return array|string[] the list of class names
     */
    protected function getTargetEntitiesConfigs()
    {
        if (null === $this->targetСonfigs) {
            $this->targetСonfigs = [];

            $configs = $this->assocBuildHelper->getScopeConfigs($this->getAssociationScope());
            foreach ($configs as $config) {
                if ($this->isTargetEntityMatched($config)) {
                    $this->targetСonfigs[] = $config;
                }
            }
        }

        return $this->targetСonfigs;
    }

    /**
     * Check if target entity matched association logic
     *
     * @param ConfigInterface $config
     *
     * @return bool
     */
    protected function isTargetEntityMatched(ConfigInterface $config)
    {
        return [] !== $this->getActivitiesFromConfig($config);
    }

    /**
     * @param ConfigInterface $config
     *
     * @return array|string[]
     */
    protected function getActivitiesFromConfig(ConfigInterface $config)
    {
        return $config->get('activities', false, []);
    }
}
