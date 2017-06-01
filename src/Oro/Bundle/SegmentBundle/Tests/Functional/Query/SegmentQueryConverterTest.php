<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Query;

use Oro\Bundle\SegmentBundle\Model\RestrictionSegmentProxy;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryConverter;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SegmentQueryConverterTest extends WebTestCase
{
    /** @var SegmentQueryConverter */
    private $segmentQueryConverter;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadSegmentData::class]);
        $this->segmentQueryConverter = $this->getContainer()->get('oro_segment.query_converter.segment');
    }

    public function testConvert()
    {
        $segment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);
        $em = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager($segment->getEntity());
        $qb = $this->segmentQueryConverter->convert(new RestrictionSegmentProxy($segment, $em));
        $tableAlias = current($qb->getDQLPart('from'))->getAlias();
        $classname = $segment->getEntity();
        $expectedDqlPart = "SELECT $tableAlias.id, $tableAlias.id FROM $classname $tableAlias";
        $this->assertEquals($expectedDqlPart, $qb->getDQL());
    }

    public function testConvertWithFilter()
    {
        $segment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);
        $em = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager($segment->getEntity());
        $qb = $this->segmentQueryConverter->convert(new RestrictionSegmentProxy($segment, $em));
        $tableAlias = current($qb->getDQLPart('from'))->getAlias();
        $expectedDqlPart = "WHERE LOWER($tableAlias.name) LIKE LOWER(:";
        $this->assertContains($expectedDqlPart, $qb->getDQL());
    }

    public function testConvertWithOrder()
    {
        $segment = $this->getReference(LoadSegmentData::SEGMENT_STATIC_WITH_FILTER_AND_SORTING);
        $em = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager($segment->getEntity());
        $qb = $this->segmentQueryConverter->convert(new RestrictionSegmentProxy($segment, $em));
        $tableAlias = current($qb->getDQLPart('from'))->getAlias();
        $expectedDqlPart = "ORDER BY $tableAlias.name DESC";
        $this->assertContains($expectedDqlPart, $qb->getDQL());
    }
}
