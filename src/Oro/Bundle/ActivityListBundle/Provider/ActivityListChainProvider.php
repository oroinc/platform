<?php

namespace Oro\Bundle\ActivityListBundle\Provider;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager as Config;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListGroupProviderInterface;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * Class ActivityListChainProvider
 * @package Oro\Bundle\ActivityListBundle\Provider
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ActivityListChainProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActivityListProviderInterface[] */
    protected $providers;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var EntityRoutingHelper */
    protected $routingHelper;

    /** @var array */
    protected $targetClasses = [];

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param ConfigManager       $configManager
     * @param TranslatorInterface $translator
     * @param EntityRoutingHelper $routingHelper
     * @param HtmlTagHelper       $htmlTagHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        EntityRoutingHelper $routingHelper,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager  = $configManager;
        $this->translator     = $translator;
        $this->routingHelper  = $routingHelper;
        $this->htmlTagHelper  = $htmlTagHelper;
    }

    /**
     * Add activity list provider
     *
     * @param ActivityListProviderInterface $provider
     */
    public function addProvider(ActivityListProviderInterface $provider)
    {
        $this->providers[$provider->getActivityClass()] = $provider;
    }

    /**
     * Get array with all target classes (entities where activity can be assigned to)
     *
     * @param bool $regenerateCaches
     * @return array
     */
    public function getTargetEntityClasses($regenerateCaches = false)
    {
        if (empty($this->targetClasses)) {
            /** @var ConfigIdInterface[] $configIds */
            $configIds = $this->configManager->getIds('entity', null, false, $regenerateCaches);
            foreach ($configIds as $configId) {
                foreach ($this->providers as $provider) {
                    if ($provider->isApplicableTarget($configId, $this->configManager)
                        && !in_array($configId->getClassName(), $this->targetClasses)
                    ) {
                        $this->targetClasses[] = $configId->getClassName();
                        continue;
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
     * Returns new activity list entity for given activity
     *
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
     * Returns updated activity list entity for given activity
     *
     * @param object        $entity
     * @param EntityManager $entityManager
     *
     * @return ActivityList
     */
    public function getUpdatedActivityList($entity, EntityManager $entityManager)
    {
        $provider        = $this->getProviderForEntity($entity);
        $existListEntity = $entityManager->getRepository(ActivityList::ENTITY_NAME)->findOneBy(
            [
                'relatedActivityClass' => $this->doctrineHelper->getEntityClass($entity),
                'relatedActivityId'    => $this->doctrineHelper->getSingleEntityIdentifier($entity)
            ]
        );

        if ($existListEntity) {
            return $this->getActivityListEntityForEntity(
                $entity,
                $provider,
                ActivityList::VERB_UPDATE,
                $existListEntity
            );
        }

        return null;
    }

    /**
     * @param Config $config
     *
     * @return array
     */
    public function getActivityListOption(Config $config)
    {
        $entityConfigProvider = $this->configManager->getProvider('entity');
        $templates            = [];

        foreach ($this->providers as $provider) {
            $hasComment = false;

            if ($provider instanceof CommentProviderInterface) {
                $hasComment = $provider->hasComments($this->configManager, $provider->getActivityClass());
            }
            $template = $provider->getTemplate();
            if ($provider instanceof ActivityListGroupProviderInterface &&
                $config->get('oro_activity_list.grouping')) {
                $template = $provider->getGroupedTemplate();
            }

            $entityConfig = $entityConfigProvider->getConfig($provider->getActivityClass());
            $templates[$this->routingHelper->encodeClassName($provider->getActivityClass())] = [
                'icon'         => $entityConfig->get('icon'),
                'label'        => $this->translator->trans($entityConfig->get('label')),
                'template'     => $template,
                'routes'       => $provider->getRoutes(),
                'has_comments' => $hasComment,
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
     * @param object $entity
     *
     * @return string|null
     */
    public function getDescription($entity)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($entity)) {
                return $provider->getDescription($entity);
            }
        }

        return null;
    }

    /**
     * Get activity list provider for given activity entity
     *
     * @param $activityEntity
     *
     * @return ActivityListProviderInterface
     */
    public function getProviderForEntity($activityEntity)
    {
        return $this->getProviderByClass($this->doctrineHelper->getEntityClass($activityEntity));
    }

    /**
     * Get activity list provider for entity class name
     *
     * @param string $className
     *
     * @return ActivityListProviderInterface
     */
    public function getProviderByClass($className)
    {
        return $this->providers[$className];
    }

    /**
     * @param object                        $entity
     * @param ActivityListProviderInterface $provider
     * @param string                        $verb
     * @param ActivityList|null             $list
     *
     * @return ActivityList
     */
    protected function getActivityListEntityForEntity(
        $entity,
        ActivityListProviderInterface $provider,
        $verb = ActivityList::VERB_CREATE,
        $list = null
    ) {
        if ($provider->isApplicable($entity)) {
            if (!$list) {
                $list = new ActivityList();
            }

            $list->setSubject($provider->getSubject($entity));
            $description = $this->htmlTagHelper->stripTags(
                $this->htmlTagHelper->purify($provider->getDescription($entity))
            );
            $list->setDescription($description);
            if ($this->hasCustomDate($provider)) {
                $list->setCreatedAt($provider->getDate($entity));
                $list->setUpdatedAt($provider->getDate($entity));
            }
            if ($this->hasGrouping($provider)) {
                $list->setHead($provider->isHead($entity));
            }
            $list->setVerb($verb);

            if ($verb === ActivityList::VERB_UPDATE) {
                $activityListTargets = $list->getActivityListTargetEntities();
                foreach ($activityListTargets as $target) {
                    $list->removeActivityListTarget($target);
                }
            } else {
                $className = $this->doctrineHelper->getEntityClass($entity);
                $list->setRelatedActivityClass($className);
                $list->setRelatedActivityId($this->doctrineHelper->getSingleEntityIdentifier($entity));
                $list->setOrganization($provider->getOrganization($entity));
            }

            $targets = $provider->getTargetEntities($entity);
            foreach ($targets as $target) {
                if ($list->supportActivityListTarget($this->doctrineHelper->getEntityClass($target))) {
                    $list->addActivityListTarget($target);
                }
            }

            return $list;
        }

        return null;
    }

    /**
     * @param ActivityListProviderInterface $provider
     *
     * @return bool
     */
    protected function hasCustomDate(ActivityListProviderInterface $provider)
    {
        return $provider instanceof ActivityListDateProviderInterface;
    }

    /**
     * @param ActivityListProviderInterface $provider
     *
     * @return bool
     */
    protected function hasGrouping(ActivityListProviderInterface $provider)
    {
        return $provider instanceof ActivityListGroupProviderInterface;
    }
}
