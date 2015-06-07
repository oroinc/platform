<?php

namespace Oro\Bundle\EntityExtendBundle\ORM;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ExtendEntityAliasProvider implements EntityAliasProviderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAlias($entityClass)
    {
        // exclude hidden entities
        $model = $this->configManager->getConfigEntityModel($entityClass);
        if ($model && $model->getMode() === ConfigModelManager::MODE_HIDDEN) {
            return false;
        }

        // custom entities
        if (ExtendHelper::isCustomEntity($entityClass)) {
            $name = 'Extend' . ExtendHelper::getShortClassName($entityClass);

            return new EntityAlias(
                strtolower($name),
                strtolower(Inflector::pluralize($name))
            );
        }

        return null;
    }
}
