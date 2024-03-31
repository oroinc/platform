<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The base class for fixtures that add activity lists for a specific entity.
 */
abstract class AddActivityListsData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const BATCH_SIZE = 2000;

    /**
     * Adds activity lists data for existing activity records only if we are in update process
     * (check on installed parameter)
     */
    public function addActivityListsForActivityClass(
        ObjectManager $manager,
        string $activityClass,
        string $ownerField = '',
        string $organizationField = ''
    ): void {
        if ($this->container->get(ApplicationState::class)->isInstalled()
            && !$this->hasRecordsInActivityList($manager, $activityClass)
        ) {
            $provider = $this->container->get('oro_activity_list.provider.chain');
            $queryBuilder = $manager->getRepository($activityClass)->createQueryBuilder('entity');
            $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
            $iterator->setBufferSize(self::BATCH_SIZE);
            $itemsCount = 0;
            $entities   = [];
            foreach ($iterator as $entity) {
                $entities[] = $entity;
                $itemsCount++;
                if (0 == $itemsCount % self::BATCH_SIZE) {
                    $this->saveActivityLists($manager, $provider, $entities, $ownerField, $organizationField);
                    $entities = [];
                }
            }
            if ($itemsCount % self::BATCH_SIZE > 0) {
                $this->saveActivityLists($manager, $provider, $entities, $ownerField, $organizationField);
            }
        }
    }

    protected function hasRecordsInActivityList(ObjectManager $manager, string $activityClass): bool
    {
        $activityList = $manager->getRepository(ActivityList::class)
            ->createQueryBuilder('activityList')
            ->select('activityList.id')
            ->where('activityList.relatedActivityClass = :activityClass')
            ->setMaxResults(1)
            ->getQuery()
            ->setParameter('activityClass', $activityClass)
            ->getOneOrNullResult();

        return (bool)$activityList;
    }

    protected function saveActivityLists(
        ObjectManager $manager,
        ActivityListChainProvider $provider,
        array $entities,
        string $ownerField = '',
        string $organizationField = ''
    ): void {
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($entities as $entity) {
            if ($ownerField && $organizationField) {
                $owner = $accessor->getValue($entity, $ownerField);
                if ($owner instanceof User) {
                    $this->setSecurityContext($owner, $accessor->getValue($entity, $organizationField));
                }
            }
            $activityListEntity = $provider->getActivityListEntitiesByActivityEntity($entity);
            if ($activityListEntity) {
                $manager->persist($activityListEntity);
            }
        }
        $manager->flush();
        $manager->clear();
    }

    protected function setSecurityContext(User $user, Organization $organization = null): void
    {
        $tokenStorage = $this->container->get('security.token_storage');
        if ($organization) {
            $token = new UsernamePasswordOrganizationToken(
                $user,
                'main',
                $organization,
                $user->getUserRoles()
            );
        } else {
            $token = new UsernamePasswordToken($user, 'main', $user->getUserRoles());
        }
        $tokenStorage->setToken($token);
    }
}
