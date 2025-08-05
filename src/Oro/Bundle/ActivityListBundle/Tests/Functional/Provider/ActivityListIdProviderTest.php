<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures\LoadActivityData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ActivityListIdProviderTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadActivityData::class]);
    }

    public function testGetActivityListIdsAreSorted()
    {
        $entityClass = 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget';

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var ActivityListRepository $repository */
        $repository = $em->getRepository(ActivityList::class);

        $activityList1 = $repository->findOneBy(['subject' => 'activity_test1']);
        $activityList1->setUpdatedAt(new \DateTime('now - 10 seconds'));

        $activityList2 = $repository->findOneBy(['subject' => 'activity_test2']);

        $em->flush($activityList1);

        $qb = $repository->getBaseActivityListQueryBuilder($entityClass, []);

        $idsDesc = $this->getContainer()->get('oro_activity_list.provider.identifiers')->getActivityListIds(
            $qb,
            $entityClass,
            0,
            [],
            []
        );

        $idsAsc = $this->getContainer()->get('oro_activity_list.provider.identifiers')->getActivityListIds(
            $qb,
            $entityClass,
            0,
            [],
            ['action' => 'prev']
        );

        $this->assertEquals([
            $activityList2->getId(),
            $activityList1->getId(),
        ], $idsDesc);

        $this->assertEquals([
            $activityList1->getId(),
            $activityList2->getId(),
        ], $idsAsc);
    }
}
