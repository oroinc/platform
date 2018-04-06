<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\Repository\ReportRepository;
use Oro\Bundle\ReportBundle\Tests\Functional\DataFixtures\LoadReportsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class ReportRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        // Delete unneeded here 'Campaign Performance' report, which can be loaded by
        // Oro\Bridge\MarketingCRM\Migrations\Migrations\Data\ORM\LoadCampaignPerformanceReport
        $this->getRepository()->createQueryBuilder('report')->delete()->getQuery()->execute();
        $this->loadFixtures([LoadReportsData::class]);
    }

    public function testGetAllReportsBasicInfoQb()
    {
        $qb = $this->getRepository()->getAllReportsBasicInfoQb();
        $this->assertInstanceOf(QueryBuilder::class, $qb);
        $result = $qb->getQuery()->getResult();
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('id', reset($result));
        $this->assertArrayIntersectEquals([
            [
                'entity' => User::class,
                'name' => 'Report 1',
            ],
            [
                'entity' => Role::class,
                'name' => 'Report 2',
            ],
            [
                'entity' => Group::class,
                'name' => 'Report 3',
            ],
        ], $result);
    }

    public function testGetAllReportsBasicInfoQbWithExcluded()
    {
        $qb = $this->getRepository()->getAllReportsBasicInfoQb([Role::class]);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
        $result = $qb->getQuery()->getResult();
        $this->assertCount(2, $result);
        $this->assertArrayIntersectEquals([
            [
                'entity' => User::class,
                'name' => 'Report 1',
            ],
            [
                'entity' => Group::class,
                'name' => 'Report 3',
            ],
        ], $result);
    }

    /**
     * @return ReportRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityRepositoryForClass(Report::class);
    }
}
