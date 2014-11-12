<?php

namespace Oro\Bundle\ActivityListBundle\Provider;

use Doctrine\ORM\EntityManager;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;

class ActivityListChainProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActivityListProviderInterface[] */
    protected $providers;

    /** @var ConfigManager */
    protected $configManager;

    /** @var array */
    protected $targetClasses = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager  $configManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager  = $configManager;
    }

    /**
     * @param ActivityListProviderInterface $provider
     */
    public function addProvider(ActivityListProviderInterface $provider)
    {
        $this->providers[$provider->getActivityClass()] = $provider;
    }

    /**
     * @return array
     */
    public function getTargetEntityClasses()
    {
        if (empty($this->targetClasses)) {
            /** @var ConfigIdInterface[] $configIds */
            $configIds = $this->configManager->getIds('entity');
            foreach ($configIds as $configId) {
                foreach ($this->providers as $provider) {
                    if ($provider->isApplicableTarget($configId, $this->configManager)
                        && !in_array($configId->getClassName(), $this->targetClasses)
                    ) {
                        $this->targetClasses[] = $configId->getClassName();
                    }
                }
            }
        }

        return $this->targetClasses;
    }

    /**
     * Get array with supported activity classes
     *
     * @return array
     */
    public function getSupportedActivities()
    {
        return array_keys($this->providers);
    }

    /**
     * Check if given activity entity supports by activity list providers
     *
     * @param $entity
     *
     * @return bool
     */
    public function isSupportedEntity($entity)
    {
        return in_array($this->doctrineHelper->getEntityClass($entity), array_keys($this->providers));
    }

    /**
     * @param object $activityEntity
     *
     * @return ActivityList
     */
    public function getActivityListEntitiesByActivityEntity($activityEntity)
    {
        $provider = $this->getProviderForEntity($activityEntity);
        return $this->getActivityListEntityForEntity($activityEntity, $provider);
    }

    /**
     * @param object        $entity
     * @param EntityManager $entityManager
     *
     * @return ActivityList
     */
    public function getUpdatedActivityList($entity, EntityManager $entityManager)
    {
        $provider        = $this->getProviderForEntity($entity);
        $existListEntity = $entityManager->getRepository('OroActivityListBundle:ActivityList')->findOneBy(
            [
                'relatedActivityClass' => $this->doctrineHelper->getEntityClass($entity),
                'relatedActivityId'    => $this->doctrineHelper->getSingleEntityIdentifier($entity)
            ]
        );

        if ($existListEntity) {
            return $this->getActivityListEntityForEntity(
                $entity,
                $provider,
                ActivityListManager::STATE_UPDATE,
                $existListEntity
            );
        }

        return null;
    }

    /**
     * @param object                        $entity
     * @param ActivityListProviderInterface $provider
     * @param string                        $state
     * @param ActivityList|null             $list
     *
     * @return ActivityList
     */
    protected function getActivityListEntityForEntity(
        $entity,
        ActivityListProviderInterface $provider,
        $state = ActivityListManager::STATE_CREATE,
        $list = null
    ) {
        if (!$list) {
            $list = new ActivityList();
        }

        /** @var ConfigProvider $configProvider */
        $configProvider = $this->configManager->getProvider('ownership');
        $accessor       = PropertyAccess::createPropertyAccessor();
        $className      = $this->doctrineHelper->getEntityClass($entity);

        if ($configProvider->hasConfig($className)) {
            $config = $configProvider->getConfig($className);
            if ($config->get('owner_type') == OwnershipType::OWNER_TYPE_USER) {
                $list->setOrganization($accessor->getValue($entity, $config->get('organization_field_name')));
                $list->setOwner($accessor->getValue($entity, $config->get('owner_field_name')));
            }
        }

        $list->setRelatedActivityClass($this->doctrineHelper->getEntityClass($entity));
        $list->setRelatedActivityId($this->doctrineHelper->getSingleEntityIdentifier($entity));
        $list->setSubject($provider->getSubject($entity));
        $list->setVerb($state);
        $list->setData($provider->getData($entity));

        $targets = $provider->getTargetEntities($entity);
        if ($state === ActivityListManager::STATE_UPDATE) {
            $activityListTargets = $list->getActivityListTargetEntities();
            foreach ($activityListTargets as $target) {
                $list->removeActivityListTarget($target);
            }
        }

        if (!empty($targets)) {
            foreach ($targets as $target) {
                if ($list->supportActivityListTarget(get_class($target))) {
                    $list->addActivityListTarget($target);
                }
            }
        }

        return $list;
    }

    /**
     * @return array
     */
    public function getActivityListOption()
    {
        $entityConfigProvider = $this->configManager->getProvider('entity');

        $templates = [];
        foreach ($this->providers as $provider) {
            $entityConfig = $entityConfigProvider->getConfig($provider->getActivityClass());
            $templates[$provider->getActivityClass()] = [
                'icon'     => $entityConfig->get('icon'),
                'label'    => $entityConfig->get('label'),
                'template' => $provider->getTemplate(),
                'routes'   => $provider->getRoutes(),
            ];
        }

        return $templates;
    }

    /**
     * @param object $entity
     *
     * @return string|null
     */
    public function getSubject($entity)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($entity)) {
                return $provider->getSubject($entity);
            }
        }

        return null;
    }

    /**
     * Get activity list provider for given activity entity
     *
     * @param $entity
     *
     * @return ActivityListProviderInterface
     */
    protected function getProviderForEntity($entity)
    {
        return $this->providers[$this->doctrineHelper->getEntityClass($entity)];
    }
}
