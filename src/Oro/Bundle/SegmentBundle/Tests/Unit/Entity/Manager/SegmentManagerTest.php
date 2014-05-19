<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class SegmentManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var SegmentManager */
    protected $manager;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'getRepository'])
            ->getMock();


        $this->manager = new SegmentManager($this->em);
    }

    protected function tearDown()
    {
        unset($this->em, $this->repository, $this->manager);
    }

    public function testGetSegmentTypeChoices()
    {
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();

        $this->em->expects($this->any())->method('getRepository')
            ->with('OroSegmentBundle:SegmentType')
            ->will($this->returnValue($this->repository));

        $type = new SegmentType('test');
        $type->setLabel('testLabel');

        $types = [$type];
        $this->repository->expects($this->once())->method('findAll')
            ->will($this->returnValue($types));

        $result = $this->manager->getSegmentTypeChoices();
        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);

        $this->assertSame(['test' => 'testLabel'], $result);
    }

    /**
     * @dataProvider segmentProvider
     *
     * @param $segmentsCount integer
     * @param $term string|null
     * @param $page integer
     * @param $skippedParameter integer
     */
    public function testGetSegmentByEntityName($segmentsCount, $term, $page, $skippedParameter = null)
    {
        $entityName     = 'Acme\Entity\Demo';
        $offset         = is_numeric($page) && $page > 1 ? ($page - 1) * SegmentManager::PER_PAGE : 0;
        $segments       = $this->generateSegments($segmentsCount);
        $expectedResult = $this->imitateResult($segments, $offset, $term, $skippedParameter);

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getResult'))
            ->getMockForAbstractClass();

        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($expectedResult['results']));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('where')
            ->will($this->returnSelf());

        $callAndWhereCounts = 0;
        $callSetParametersCounts = 1;

        if (!empty($term)) {
            $callAndWhereCounts++;
            $callSetParametersCounts++;
        }

        if (!empty($skippedParameter)) {
            $callAndWhereCounts++;
            $callSetParametersCounts++;
        }

        $qb->expects($this->exactly($callAndWhereCounts))
            ->method('andWhere')
            ->will($this->returnSelf());
        $qb->expects($this->exactly($callSetParametersCounts))
            ->method('setParameter')
            ->will($this->returnSelf());

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(SegmentManager::PER_PAGE + 1)
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('OroSegmentBundle:Segment'))
            ->will($this->returnValue($repo));

        $realResult = $this->manager->getSegmentByEntityName($entityName, $term, $page, $skippedParameter);

        foreach ($realResult['results'] as $key => $item) {
            /** @var Segment $expectedItem */
            $expectedItem = $expectedResult['results'][$key];
            $this->assertEquals($expectedItem->getName(), $item['text']);
            $this->assertEquals('segment', $item['type']);
        }
    }

    /**
     * @return array
     */
    public function segmentProvider()
    {
        return array(
            'all right' => array(
                'segmentsCount' => SegmentManager::PER_PAGE * 2,
                'term'          => 'test',
                'page'          => 1,
            ),
            'specific term' => array(
                'segmentsCount' => SegmentManager::PER_PAGE * 2,
                'term'          => '_' . SegmentManager::PER_PAGE,
                'page'          => 1,
            ),
            'wrong page number' => array(
                'segmentsCount' => SegmentManager::PER_PAGE * 2,
                'term'          => 'test',
                'page'          => 0,
            ),
            'empty term' => array(
                'segmentsCount' => SegmentManager::PER_PAGE * 2,
                'term'          => null,
                'page'          => 1,
            ),
            'less than page size' => array(
                'segmentsCount' => SegmentManager::PER_PAGE - 1,
                'term'          => null,
                'page'          => 1,
            ),
            'second page' => array(
                'segmentsCount' => SegmentManager::PER_PAGE * 2,
                'term'          => null,
                'page'          => 2,
            ),
            'with skipped parameter' => array(
                'segmentsCount'    => SegmentManager::PER_PAGE * 2,
                'term'             => null,
                'page'             => 1,
                'skippedParameter' => SegmentManager::PER_PAGE -1
            ),
            'with skipped parameter on second page' => array(
                'segmentsCount'    => SegmentManager::PER_PAGE * 2,
                'term'             => null,
                'page'             => 2,
                'skippedParameter' => SegmentManager::PER_PAGE -1
            )
        );
    }

    protected function imitateResult($segments, $offset, $term, $skippedParameter)
    {
        /** @var Segment $segment */
        foreach ($segments as $key => $segment) {
            if (!empty($term) && false === strpos($segment->getName(), $term) ||
                !empty($skippedParameter) && $skippedParameter == $key + $offset
            ) {
                unset($segments[$key]);
            }
        }

        return array(
            'results' => empty($segments) ? array() : array_slice($segments, $offset, SegmentManager::PER_PAGE + 1),
        );
    }

    protected function generateSegments($count)
    {
        $segments = array();

        for ($i = 0; $i < $count; $i++) {
            $segment = new Segment();
            $segment->setName('test_' . $i);
            $segments[] = $segment;
        }

        return $segments;
    }
}
