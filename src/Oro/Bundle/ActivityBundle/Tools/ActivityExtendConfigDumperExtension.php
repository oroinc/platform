<?php

namespace Oro\Bundle\ActivityBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationExtendConfigDumperExtension;

class ActivityExtendConfigDumperExtension extends AssociationExtendConfigDumperExtension
{
    /**
     * {@inheritdoc}
     */
    protected function targetEntityMatch(ConfigInterface $config)
    {
        $activityItemNames = $config->get('items', false, []);

        return !empty($activityItemNames);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationScope()
    {
        return 'activity';
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(array &$extendConfigs)
    {
        $targetEntityConfigs = $this->getTargetEntitiesConfigs();

        foreach ($targetEntityConfigs as $targetEntityConfig) {
            $targetEntityName  = $targetEntityConfig->getId()->getClassName();
            $activityEntityNames = $targetEntityConfig->get('items', false, []);

            // create many-to-many unidirectional relation between/from activity to association entity:
            // e.g. contact, account, etc
            foreach ($activityEntityNames as $activityEntityName) {
                $this->createRelation($activityEntityName, $targetEntityName, 'manyToMany');
            }
        }
    }
}
