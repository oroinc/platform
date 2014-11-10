<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

/**
 * This fixture adds activity lists data for existing activity records.
 */
abstract class AddActivityListsData extends AbstractFixture implements ContainerAwareInterface
{
    const BATCH_SIZE = 300;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Adds activity lists data for existing activity records.
     *
     * @param ObjectManager $manager
     * @param string        $activityClass Activity class we need to add activity list data
     */
    public function addActivityListsForActivityClass(ObjectManager $manager, $activityClass)
    {
        $recordsCount = $manager->getRepository('OroActivityListBundle:ActivityList')->createQueryBuilder('al')
            ->select('COUNT(al.id)')
            ->where('al.relatedActivityClass = :activityClass')
            ->setParameter('activityClass', $activityClass)
            ->getQuery()
            ->getSingleScalarResult();

        if ($recordsCount === 0) {
            $provider = $this->container->get('oro_activity_list.provider.chain');
            $queryBuilder = $manager->getRepository($activityClass)->createQueryBuilder('entity');
            $iterator = new BufferedQueryResultIterator($queryBuilder);
            $iterator->setBufferSize(self::BATCH_SIZE);

            $itemsCount = 0;
            $entities = [];

            foreach ($iterator as $entity) {
                $entities[] = $entity;
                $itemsCount++;

                if (0 == $itemsCount % self::BATCH_SIZE) {
                    $this->saveActivityLists($manager, $provider, $entities);
                    $entities = [];
                }
            }

            if ($itemsCount % static::BATCH_SIZE > 0) {
                $this->saveActivityLists($manager, $provider, $entities);
            }
        }
    }

    /**
     * @param ObjectManager             $manager
     * @param ActivityListChainProvider $provider
     * @param array                     $entities
     */
    protected function saveActivityLists(ObjectManager $manager, ActivityListChainProvider $provider, $entities)
    {
        foreach ($entities as $entity) {
            $manager->persist($provider->getActivityListEntitiesByActivityEntity($entity));
        }
        $manager->flush();
    }
}
