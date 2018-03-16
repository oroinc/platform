<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateActivityListOwner extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const BATCH_SIZE = 200;

    /** @var ContainerInterface */
    protected $container;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->updateActivityListOwner($manager);
    }

    /**
     * Update ActivityList Owner
     *
     * @param ObjectManager $manager
     */
    public function updateActivityListOwner(ObjectManager $manager)
    {
        if ($this->container->hasParameter('installed') && $this->container->getParameter('installed')) {
            /** @var ActivityListChainProvider $activitiesProvider */
            $activitiesProvider = $this->container->get('oro_activity_list.provider.chain');

            /** @var QueryBuilder $activityListBuilder */
            $queryBuilder = $manager
                ->getRepository('OroActivityListBundle:ActivityList')
                ->createQueryBuilder('e');

            $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
            $iterator->setBufferSize(self::BATCH_SIZE);
            $itemsCount = 0;
            $entities = [];
            foreach ($iterator as $entity) {
                $entities[] = $entity;
                $itemsCount++;
                if (0 === $itemsCount % self::BATCH_SIZE) {
                    $this->saveActivityListOwner($manager, $activitiesProvider, $entities);
                    $entities = [];
                }
            }

            if ($itemsCount % static::BATCH_SIZE > 0) {
                $this->saveActivityListOwner($manager, $activitiesProvider, $entities);
            }
        }
    }

    /**
     * Update activity
     *
     * @param ObjectManager             $manager
     * @param ActivityListChainProvider $provider
     * @param array                     $entities
     */
    public function saveActivityListOwner(ObjectManager $manager, ActivityListChainProvider $provider, $entities)
    {
        /** @var ActivityList $entity */
        foreach ($entities as $entity) {
            /** @var ActivityOwner[] $activityOwners */
            $activityOwners = $this->prepareActivityOwnerData($entity, $provider);
            foreach ($activityOwners as $activityOwner) {
                if (!$entity->hasActivityOwner($activityOwner)) {
                    $entity->addActivityOwner($activityOwner);
                    $manager->persist($entity);
                }
            }
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * Get ActivityOwner entity by ActivityList entity
     *
     * @param ActivityList              $entity
     * @param ActivityListChainProvider $provider
     *
     * @return ActivityOwner[]
     */
    protected function prepareActivityOwnerData($entity, $provider)
    {
        /** @var  DoctrineHelper $helper */
        $helper = $this->container->get('oro_entity.doctrine_helper');

        $relatedActivityEntity = $helper->getEntity(
            $entity->getRelatedActivityClass(),
            $entity->getRelatedActivityId()
        );

        $entityProvider = $provider->getProviderForEntity($entity->getRelatedActivityClass());
        $activityOwners = $entityProvider->getActivityOwners($relatedActivityEntity, $entity);

        return $activityOwners;
    }
}
