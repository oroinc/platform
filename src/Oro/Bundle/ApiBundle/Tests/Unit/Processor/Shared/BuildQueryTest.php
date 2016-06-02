<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildQuery;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class BuildQueryTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $criteriaConnector;

    /** @var BuildQuery */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->criteriaConnector = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\CriteriaConnector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new BuildQuery($this->doctrineHelper, $this->criteriaConnector);
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $qb = $this->getQueryBuilderMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setCriteria(new Criteria($resolver));
        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    public function testProcess()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User';

        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $this->criteriaConnector->expects($this->once())
            ->method('applyCriteria');

        $this->context->setClassName($className);
        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        $this->assertEquals(
            sprintf('SELECT e FROM %s e', $className),
            $query->getDQL()
        );
    }
}
