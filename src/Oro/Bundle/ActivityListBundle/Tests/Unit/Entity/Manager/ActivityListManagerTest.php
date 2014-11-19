<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Manager;


use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

class ActivityListManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityListManager */
    protected $activityListManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $nameFormatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $pager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityListFilterHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    public function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();
        $this->nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()->getMock();
        $this->pager = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager')
            ->disableOriginalConstructor()->getMock();
        $this->config = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\UserConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->provider = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()->getMock();
        $this->activityListFilterHelper = $this
            ->getMockBuilder('Oro\Bundle\ActivityListBundle\Filter\ActivityListFilterHelper')
            ->disableOriginalConstructor()->getMock();
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $this->doctrine->expects($this->any())->method('getManager')->willReturn($this->em);

        $this->activityListManager = new ActivityListManager(
            $this->doctrine,
            $this->securityFacade,
            $this->nameFormatter,
            $this->pager,
            $this->config,
            $this->provider,
            $this->activityListFilterHelper,
            $this->activityListManager
        );
    }

    public function testGetRepository()
    {
        $this->em->expects($this->once())->method('getRepository')->with('OroActivityListBundle:ActivityList');
        $this->activityListManager->getRepository();
    }

    public function testGetList()
    {
        $classMeta = new ClassMetadata('Oro\Bundle\ActivityListBundle\Entity\ActivityList');
        $repo = new ActivityListRepository($this->em, $classMeta);
        $testClass = 'Acme\TestBundle\Entity\TestEntity';
        $testId = 12;
        $page = 2;
        $filter = [];
        $configPerPare = 10;

        $this->config->expects($this->any())->method('get')
            ->willReturnCallback(
                function ($configKey) {
                    if ($configKey === 'oro_activity_list.per_page') {
                        return 10;
                    }
                    if ($configKey === 'oro_activity_list.sorting_field') {
                        return 'createdBy';
                    }
                    return 'ASC';
                }
            );

        $qb = new QueryBuilder($this->em);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $this->em->expects($this->once())->method('getRepository')->willReturn($repo);
        $this->activityListFilterHelper->expects($this->once())->method('addFiltersToQuery')->with($qb, $filter);
        $this->pager->expects($this->once())->method('setQueryBuilder')->with($qb);
        $this->pager->expects($this->once())->method('setPage')->with($page);

        $this->pager->expects($this->once())->method('setMaxPerPage')->with($configPerPare);
        $this->pager->expects($this->once())->method('init');
        $this->pager->expects($this->once())->method('getResults')->willReturn([]);

        $this->activityListManager->getList($testClass, $testId, $filter, $page);

        $expectedDQL = 'SELECT activity FROM Oro\Bundle\ActivityListBundle\Entity\ActivityList activity '
            . 'INNER JOIN activity.test_entity_9d8125dd r WHERE r.id = :entityId ORDER BY activity.createdBy ASC';
        $this->assertEquals($expectedDQL, $qb->getDQL());
        $this->assertEquals($testId, $qb->getParameters()->first()->getValue());
    }

    public function testGetListCount()
    {
        $classMeta = new ClassMetadata('Oro\Bundle\ActivityListBundle\Entity\ActivityList');
        $repo = new ActivityListRepository($this->em, $classMeta);
        $testClass = 'Acme\TestBundle\Entity\TestEntity';
        $testId = 50;
        $filter = [];

        $this->config->expects($this->any())->method('get')
            ->willReturnCallback(
                function ($configKey) {
                    if ($configKey === 'oro_activity_list.sorting_field') {
                        return 'createdBy';
                    }
                    return 'DESC';
                }
            );

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setConstructorArgs([$this->em])
            ->setMethods(['getQuery'])->getMock();

        $query = $this->getMockForAbstractClass(
            'Doctrine\ORM\AbstractQuery',
            [$this->em],
            '',
            false,
            true,
            true,
            ['getSingleScalarResult']
        );
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn(1);

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $this->em->expects($this->once())->method('getRepository')->willReturn($repo);
        $this->activityListFilterHelper->expects($this->once())->method('addFiltersToQuery')->with($qb, $filter);

        $this->activityListManager->getListCount($testClass, $testId, $filter);

        $expectedDQL = 'SELECT COUNT(activity.id) FROM Oro\Bundle\ActivityListBundle\Entity\ActivityList activity '
            . 'INNER JOIN activity.test_entity_9d8125dd r WHERE r.id = :entityId ORDER BY activity.createdBy DESC';
        $this->assertEquals($expectedDQL, $qb->getDQL());
        $this->assertEquals($testId, $qb->getParameters()->first()->getValue());
    }
}
