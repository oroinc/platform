<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

//use Oro\Bundle\ActivityBundle\Event\ActivityEvent;
//use Oro\Bundle\ActivityBundle\Event\Events;
//use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
//use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
//use Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder;
//use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
//use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
//use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EmailActivityManager extends ActivityManager
{
    /** @var ConfigManager */
    protected $entityConfigManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassResolver $entityClassResolver,
        ConfigProvider $activityConfigProvider,
        ConfigProvider $groupingConfigProvider,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        AssociationManager $associationManager,
        ConfigManager $entityConfigManager
    ) {
        parent::__construct($doctrineHelper, $entityClassResolver, $activityConfigProvider, $groupingConfigProvider, $entityConfigProvider, $extendConfigProvider, $associationManager);
        $this->entityConfigManager = $entityConfigManager;
    }

    /**
     * Returns a query builder that could be used for fetching the list of entities
     * associated with the given activity
     *
     * @param string        $activityClassName The FQCN of the activity entity
     * @param mixed         $filters           Criteria is used to filter activity entities
     *                                         e.g. ['age' => 20, ...] or \Doctrine\Common\Collections\Criteria
     * @param array|null    $joins             Additional associations required to filter activity entities
     * @param int|null      $limit             The maximum number of items per page
     * @param int|null      $page              The page number
     * @param string|null   $orderBy           The ordering expression for the result
     * @param callable|null $callback          A callback function which can be used to modify child queries
     *                                         function (QueryBuilder $qb, $targetEntityClass)
     *
     * @return SqlQueryBuilder|null SqlQueryBuilder object or NULL if the given entity type has no activity associations
     */
    public function getActivityTargetsQueryBuilder(
        $activityClassName,
        $filters,
        $joins = null,
        $limit = null,
        $page = null,
        $orderBy = null,
        $callback = null
    ) {
        $targets = $this->getActivityTargets($activityClassName);
        if (empty($targets)) {
            return null;
        }

        if ($filters['skip_custom_entity']) {
            foreach ($targets as $targetClass => $targetAliase) {
                if ($this->entityConfigManager->hasConfig($targetClass)) {
                    $config = $this->entityConfigManager->getEntityConfig('extend', $targetClass);

                    if ($config->get('owner') !== 'System') {
                        unset($targets[$targetClass]);
                    }
                }
            }
        }

        $filters = $filters['criteria'];

        return $this->associationManager->getMultiAssociationsQueryBuilder(
            $activityClassName,
            $filters,
            $joins,
            $targets,
            $limit,
            $page,
            $orderBy,
            $callback
        );
    }
}
