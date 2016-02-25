<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Doctrine\Common\Collections\Expr\Comparison;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\GetList\BuildQuery;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class BuildQueryTest extends OrmRelatedTestCase
{
    /** @var BuildQuery */
    protected $processor;

    /** @var GetListContext */
    protected $context;

    protected function setUp()
    {
        parent::setUp();

        $configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new GetListContext($configProvider, $metadataProvider);

        $this->processor = new BuildQuery($this->doctrineHelper);
    }

    public function testProcessOnExistingQuery()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->setQuery($qb);

        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User';
        $doctrineHelper  = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(false);
        $doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $processor = new BuildQuery($doctrineHelper);
        $this->context->setClassName($className);
        $processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    public function testProcess()
    {
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);
        $criteria->andWhere(new Comparison('name', '=', 'test'));
        $this->context->setCriteria($criteria);
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');

        $this->processor->process($this->context);

        $query = $this->context->getQuery();
        $this->assertEquals(
            'SELECT e FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e WHERE e.name = :name',
            $query->getDQL()
        );
        $parameter = $query->getParameters()->first();
        $this->assertEquals('name', $parameter->getName());
        $this->assertEquals('test', $parameter->getValue());
    }
}
