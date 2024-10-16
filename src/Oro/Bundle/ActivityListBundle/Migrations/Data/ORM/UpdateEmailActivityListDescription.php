<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Email;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Updates descriptions for Email related activity list records.
 */
class UpdateEmailActivityListDescription extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const BATCH_SIZE = 500;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->updateEmailActivityDescription($manager);
    }

    private function updateEmailActivityDescription(ObjectManager $manager): void
    {
        /** @var QueryBuilder $activityListBuilder */
        $activityListBuilder = $manager->getRepository(ActivityList::class)->createQueryBuilder('e');

        $iterator = new BufferedIdentityQueryResultIterator($activityListBuilder);
        $iterator->setBufferSize(self::BATCH_SIZE);

        $itemsCount = 0;
        $entities   = [];
        $emailRepository = $manager->getRepository(Email::class);
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

    private function saveEntities(ObjectManager $manager, array $entities): void
    {
        foreach ($entities as $activity) {
            $manager->persist($activity);
        }
        $manager->flush();
        $manager->clear();
    }
}
