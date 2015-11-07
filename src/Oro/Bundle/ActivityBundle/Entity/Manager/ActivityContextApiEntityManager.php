<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EntityBundle\ORM\QueryUtils;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

class ActivityContextApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var ConfigManager */
    protected $configManager;

    /** @var RouterInterface */
    protected $router;

    /** @var EntityClassNameHelper */
    protected $classNameHelper;

    /**
     * @param ActivityManager       $activityManager
     * @param TokenStorageInterface $securityTokenStorage
     * @param ConfigManager         $configManager
     * @param RouterInterface       $router
     * @param EntityClassNameHelper $classNameHelper
     */
    public function __construct(
        ActivityManager $activityManager,
        TokenStorageInterface $securityTokenStorage,
        ConfigManager $configManager,
        RouterInterface $router,
        EntityClassNameHelper $classNameHelper
    ) {
        $this->activityManager      = $activityManager;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->configManager        = $configManager;
        $this->router               = $router;
        $this->classNameHelper      = $classNameHelper;
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

        $currentUser = $this->securityTokenStorage->getToken()->getUser();
        $userClass   = ClassUtils::getClass($currentUser);

        $queryBuilder = $this->activityManager->getActivityTargetsQueryBuilder(
            $class,
            $criteria,
            null,
            null,
            null,
            null,
            function (QueryBuilder $qb, $targetEntityClass) use ($currentUser, $userClass) {
                if ($targetEntityClass === $userClass) {
                    // Exclude current user from result
                    $qb->andWhere(
                        $qb->expr()->neq(
                            QueryUtils::getSelectExprByAlias($qb, 'entityId'),
                            $currentUser->getId()
                        )
                    );
                }
            }
        );

        if (null === $queryBuilder) {
            return [];
        }

        $result = $queryBuilder->getQuery()->getResult();
        if (empty($result)) {
            return $result;
        }

        foreach ($result as &$item) {
            $icon  = $this->configManager->getProvider('entity')->getConfig($item['entity'])->get('icon');
            $route = $this->configManager->getEntityMetadata($item['entity'])->getRoute();

            $item['entityId']        = $activity->getId();
            $item['targetId']        = $item['id'];
            $item['targetClassName'] = $this->classNameHelper->getUrlSafeClassName($item['entity']);
            $item['icon']            = $icon;
            $item['link']            = $route
                ? $this->router->generate($route, ['id' => $item['id']])
                : null;

            unset($item['id'], $item['entity']);
        }

        return $result;
    }
}
