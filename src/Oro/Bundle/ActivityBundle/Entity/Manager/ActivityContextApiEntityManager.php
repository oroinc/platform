<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ActivityBundle\Event\PrepareContextTitleEvent;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Symfony\Component\Routing\RouterInterface;

class ActivityContextApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var RouterInterface */
    protected $router;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param ObjectManager                 $om
     * @param ActivityManager               $activityManager
     * @param ConfigManager                 $configManager
     * @param RouterInterface               $router
     * @param EntityAliasResolver           $entityAliasResolver
     * @param EntityNameResolver            $entityNameResolver
     * @param DoctrineHelper                $doctrineHelper
     * @param FeatureChecker                $featureChecker
     */
    public function __construct(
        ObjectManager $om,
        ActivityManager $activityManager,
        ConfigManager $configManager,
        RouterInterface $router,
        EntityAliasResolver $entityAliasResolver,
        EntityNameResolver $entityNameResolver,
        DoctrineHelper $doctrineHelper,
        FeatureChecker $featureChecker
    ) {
        parent::__construct(null, $om);

        $this->activityManager      = $activityManager;
        $this->configManager        = $configManager;
        $this->router               = $router;
        $this->entityAliasResolver  = $entityAliasResolver;
        $this->entityNameResolver   = $entityNameResolver;
        $this->doctrineHelper       = $doctrineHelper;
        $this->featureChecker       = $featureChecker;
    }

    /**
     * Returns the context for the given activity class and id
     *
     * @param string $class The FQCN of the activity entity
     * @param        $id
     *
     * @return array
     */
    public function getActivityContext($class, $id)
    {
        $entity      = $this->doctrineHelper->getEntity($class, $id);
        $result = [];

        if (!$entity || !$entity instanceof ActivityInterface) {
            return $result;
        }

        $targets = $entity->getActivityTargets();
        $entityProvider = $this->configManager->getProvider('entity');

        foreach ($targets as $target) {
            $targetClass = ClassUtils::getClass($target);
            $targetId = $target->getId();

            if (!$this->featureChecker->isResourceEnabled($targetClass, 'entities')) {
                continue;
            }

            $item          = [];
            $config        = $entityProvider->getConfig($targetClass);
            $safeClassName = $this->entityClassNameHelper->getUrlSafeClassName($targetClass);

            $item['title'] = $this->entityNameResolver->getName($target);

            $item['activityClassAlias'] = $this->entityAliasResolver->getPluralAlias($class);
            $item['entityId']           = $id;

            $item['targetId']        = $targetId;
            $item['targetClassName'] = $safeClassName;

            $item['icon'] = $config->get('icon');
            $item['link'] = $this->getContextLink($targetClass, $targetId);

            $item = $this->dispatchContextTitle($item, $targetClass);

            $result[] = $item;
        }

        // sort list by class name (group the same classes) and then by title
        usort(
            $result,
            function ($a, $b) {
                if ($a['targetClassName'] . $a['title'] <= $b['targetClassName'] . $b['title']) {
                    return -1;
                }

                return 1;
            }
        );

        return $result;
    }

    /**
     * @param string $targetClass The FQCN of the activity target entity
     * @param int    $targetId    The identifier of the activity target entity
     *
     * @return string|null
     */
    protected function getContextLink($targetClass, $targetId)
    {
        $metadata = $this->configManager->getEntityMetadata($targetClass);
        $link     = null;
        if ($metadata) {
            try {
                $route = $metadata->getRoute('view', true);
            } catch (\LogicException $exception) {
                // Need for cases when entity does not have route.
                return null;
            }
            $link = $this->router->generate($route, ['id' => $targetId]);
        } elseif (ExtendHelper::isCustomEntity($targetClass)) {
            $safeClassName = $this->entityClassNameHelper->getUrlSafeClassName($targetClass);
            // Generate view link for the custom entity
            $link = $this->router->generate(
                'oro_entity_view',
                [
                    'id'         => $targetId,
                    'entityName' => $safeClassName

                ]
            );
        }

        return $link;
    }

    /**
     * @param $item
     * @param $targetClass
     * @return array
     */
    protected function dispatchContextTitle($item, $targetClass)
    {
        if ($this->eventDispatcher) {
            $event = new PrepareContextTitleEvent($item, $targetClass);
            $this->eventDispatcher->dispatch(PrepareContextTitleEvent::EVENT_NAME, $event);
            $item = $event->getItem();
        }

        return $item;
    }
}
