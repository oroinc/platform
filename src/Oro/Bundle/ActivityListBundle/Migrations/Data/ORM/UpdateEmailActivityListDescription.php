<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates descriptions for Email related activity list records.
 */
class UpdateEmailActivityListDescription extends AbstractFixture implements ContainerAwareInterface
{
    const BATCH_SIZE = 500;

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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->updateEmailActivityDescription($manager);
    }

    /**
     * Update activity
     */
    public function updateEmailActivityDescription(ObjectManager $manager)
    {
        /** @var QueryBuilder $activityListBuilder */
        $activityListBuilder = $manager->getRepository(ActivityList::class)->createQueryBuilder('e');

        $iterator = new BufferedIdentityQueryResultIterator($activityListBuilder);
        $iterator->setBufferSize(self::BATCH_SIZE);

        $itemsCount = 0;
        $entities   = [];
        $emailRepository = $manager->getRepository('OroEmailBundle:Email');
        $activityProvider = $this->container->get('oro_email.activity_list.provider');

        foreach ($iterator as $activity) {
            $email = $emailRepository->find($activity->getRelatedActivityId());
            if ($email) {
                $itemsCount++;
                $activity->setDescription($activityProvider->getDescription($email));
                $entities[] = $activity;
            }
            if (0 === $itemsCount % self::BATCH_SIZE) {
                $this->saveEntities($manager, $entities);
                $entities = [];
            }
        }

        if ($itemsCount % self::BATCH_SIZE > 0) {
            $this->saveEntities($manager, $entities);
        }
    }

    protected function saveEntities(ObjectManager $manager, array $entities)
    {
        foreach ($entities as $activity) {
            $manager->persist($activity);
        }
        $manager->flush();
        $manager->clear();
    }
}
