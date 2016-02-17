<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

/**
 * This fixture adds activity lists data for existing activity records.
 */
abstract class AddActivityListsData extends AbstractFixture implements ContainerAwareInterface
{
    const BATCH_SIZE = 2000;

    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Adds activity lists data for existing activity records only if we are in update process
     * (check on installed parameter)
     *
     * @param ObjectManager $manager
     * @param string        $activityClass Activity class we need to add activity list data
     * @param string        $ownerField
     * @param string        $organizationField
     */
    public function addActivityListsForActivityClass(
        ObjectManager $manager,
        $activityClass,
        $ownerField = '',
        $organizationField = ''
    ) {
        if ($this->container->hasParameter('installed') && $this->container->getParameter('installed')) {
            $provider     = $this->container->get('oro_activity_list.provider.chain');
            $queryBuilder = $manager->getRepository($activityClass)->createQueryBuilder('entity');
            $iterator     = new BufferedQueryResultIterator($queryBuilder);
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

            if ($itemsCount % static::BATCH_SIZE > 0) {
                $this->saveActivityLists($manager, $provider, $entities, $ownerField, $organizationField);
            }
        }
    }

    /**
     * @param ObjectManager             $manager
     * @param ActivityListChainProvider $provider
     * @param array                     $entities
     * @param string                    $ownerField
     * @param string                    $organizationField
     */
    protected function saveActivityLists(
        ObjectManager $manager,
        ActivityListChainProvider $provider,
        $entities,
        $ownerField = '',
        $organizationField = ''
    ) {
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($entities as $entity) {
            if ($ownerField && $organizationField) {
                $owner = $accessor->getValue($entity, $ownerField);
                if ($owner instanceof User) {
                    $this->setSecurityContext(
                        $owner,
                        $accessor->getValue($entity, $organizationField)
                    );
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

    /**
     * @param User         $user
     * @param Organization $organization|null
     */
    protected function setSecurityContext(User $user, Organization $organization = null)
    {
        $securityContext = $this->container->get('security.context');
        if ($organization) {
            $token = new UsernamePasswordOrganizationToken($user, $user->getUsername(), 'main', $organization);
        } else {
            $token = new UsernamePasswordToken($user, $user->getUsername(), 'main');
        }
        $securityContext->setToken($token);
    }
}
