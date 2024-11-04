<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityContextApiEntityManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides functionality to manage activity context data for email threads.
 */
class EmailThreadActivityContextApiEntityManager extends ActivityContextApiEntityManager
{
    private ActivityManager $activityManager;

    public function __construct(
        ObjectManager $om,
        ConfigManager $configManager,
        RouterInterface $router,
        EntityAliasResolver $entityAliasResolver,
        EntityNameResolver $entityNameResolver,
        FeatureChecker $featureChecker,
        AuthorizationCheckerInterface $authorizationChecker,
        ActivityManager $activityManager
    ) {
        parent::__construct(
            $om,
            $configManager,
            $router,
            $entityAliasResolver,
            $entityNameResolver,
            $featureChecker,
            $authorizationChecker
        );
        $this->activityManager = $activityManager;
        $this->setClass(Email::class);
    }

    #[\Override]
    protected function getActivityTargets(object $entity): array
    {
        /** @var Email $entity */

        $qb = $this->activityManager->getLimitedActivityTargetsQueryBuilder(
            Email::class,
            array_keys($this->activityManager->getActivityTargets(Email::class)),
            ['id' => $this->getThreadedEmailIds($entity->getId())]
        );
        if (null === $qb) {
            return [];
        }

        $rows = $qb->getQuery()->getArrayResult();

        $result = [];
        $resultMap = [];
        foreach ($rows as $row) {
            $key = $row['entity'] . ':' . $row['id'];
            if (!isset($resultMap[$key])) {
                $result[] = $this->doctrineHelper->getEntityReference($row['entity'], $row['id']);
                $resultMap[$key] = true;
            }
        }

        return $result;
    }

    private function getThreadedEmailIds(int $emailId): array
    {
        $rows = $this->doctrineHelper->createQueryBuilder(Email::class, 'e')
            ->select('e.id')
            ->innerJoin(Email::class, 'p', Join::WITH, 'e.id = p.id OR e.thread = p.thread')
            ->where('p.id = :id')
            ->setParameter('id', $emailId)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'id');
    }
}
