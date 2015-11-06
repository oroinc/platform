<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\Routing\RouterInterface;

class ActivityContextApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ConfigManager */
    protected $configManager;

    /** @var RouterInterface */
    protected $router;

    /** @var EntityClassNameHelper */
    protected $classNameHelper;

    public function __construct(
        ActivityManager $activityManager,
        SecurityFacade $securityFacade,
        ConfigManager $configManager,
        RouterInterface $router,
        EntityClassNameHelper $classNameHelper
    ) {
        $this->activityManager = $activityManager;
        $this->securityFacade = $securityFacade;
        $this->configManager = $configManager;
        $this->router = $router;
        $this->classNameHelper = $classNameHelper;
    }

    /**
     * Returns the context for the given activity
     *
     * @param ActivityInterface $activity
     *
     * @return array
     */
    public function getActivityContext(ActivityInterface $activity)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('id', $activity->getId()));

        $class = ClassUtils::getClass($activity);

        $queryBuilder = $this->activityManager->getActivityTargetsQueryBuilder($class, $criteria);
        if (null === $queryBuilder) {
            return [];
        }

        $result = $queryBuilder->getQuery()->getResult();
        if (empty($result)) {
            return $result;
        }

        $currentUser      = $this->securityFacade->getLoggedUser();
        $currentUserClass = ClassUtils::getClass($currentUser);
        $currentUserId    = $currentUser->getId();
        $result           = array_values(
            array_filter(
                $result,
                function ($item) use ($currentUserClass, $currentUserId) {
                    return !($item['entity'] === $currentUserClass && $item['id'] == $currentUserId);
                }
            )
        );

        foreach ($result as &$item) {
            $route = $this->configManager->getEntityMetadata($item['entity'])->getRoute();

            $item['entityId']        = $activity->getId();
            $item['targetId']        = $item['id'];
            $item['targetClassName'] = $this->classNameHelper->getUrlSafeClassName($item['entity']);
            $item['icon']            = $this->configManager->getProvider('entity')->getConfig($item['entity'])
                ->get('icon');
            $item['link']            = $route
                ? $this->router->generate($route, ['id' => $item['id']])
                : null;

            unset($item['id'], $item['entity']);
        }

        return $result;
    }
}
