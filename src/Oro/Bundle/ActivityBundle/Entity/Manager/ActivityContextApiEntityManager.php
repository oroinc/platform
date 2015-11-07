<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\ORM\QueryUtils;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class ActivityContextApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var ConfigManager */
    protected $configManager;

    /** @var RouterInterface */
    protected $router;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param ObjectManager         $om
     * @param ActivityManager       $activityManager
     * @param TokenStorageInterface $securityTokenStorage
     * @param ConfigManager         $configManager
     * @param RouterInterface       $router
     * @param EntityAliasResolver   $entityAliasResolver
     */
    public function __construct(
        ObjectManager $om,
        ActivityManager $activityManager,
        TokenStorageInterface $securityTokenStorage,
        ConfigManager $configManager,
        RouterInterface $router,
        EntityAliasResolver $entityAliasResolver
    ) {
        parent::__construct(null, $om);

        $this->activityManager      = $activityManager;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->configManager        = $configManager;
        $this->router               = $router;
        $this->entityAliasResolver  = $entityAliasResolver;
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
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('id', $id));

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

            $item['activityClassAlias'] = $this->entityAliasResolver->getPluralAlias($class);
            $item['entityId']           = $id;

            $item['targetId']        = $item['id'];
            $item['targetClassName'] = $this->entityClassNameHelper->getUrlSafeClassName($item['entity']);

            $item['icon'] = $icon;
            $item['link'] = $route
                ? $this->router->generate($route, ['id' => $item['id']])
                : null;

            unset($item['id'], $item['entity']);
        }

        return $result;
    }
}
