<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get;

use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Get\BuildQuery;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class BuildQueryTest extends OrmRelatedTestCase
{
    /** @var BuildQuery */
    protected $processor;

    /** @var GetContext */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new BuildQuery($this->doctrineHelper);
        $this->context = new GetContext($this->configProvider, $this->metadataProvider);
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->setCriteria(new Criteria($resolver));
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

    public function testProcessOnSingleIdEntity()
    {
        $this->assertFalse($this->context->hasQuery());
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->context->setId(12);

        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        $this->assertEquals(
            'SELECT e FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e WHERE e.id = :id',
            $query->getDQL()
        );
        /** @var Parameter $parameter */
        $parameter = $query->getParameters()->first();
        $this->assertEquals('id', $parameter->getName());
        $this->assertEquals(12, $parameter->getValue());
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage The entity identifier cannot be an array because the entity
     * "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User" has single primary key.
     */
    public function testProcessOnSingleIdEntityWithGivenArrayId()
    {
        $this->assertFalse($this->context->hasQuery());
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->context->setId([2, 4]);

        $this->processor->process($this->context);
    }

    public function testProcessOnCompositeIdEntity()
    {
        $this->assertFalse($this->context->hasQuery());
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity');
        $this->context->setId(['id' => 23, 'title' => 'test']);

        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        $this->assertEquals(
            'SELECT e FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity e '
                . 'WHERE e.id = :id1 AND e.title = :id2',
            $query->getDQL()
        );

        /** @var Parameter $parameter */
        $parameters = $query->getParameters();
        $idParameter = $parameters[0];
        $this->assertEquals('id1', $idParameter->getName());
        $this->assertEquals(23, $idParameter->getValue());
        $titleParameter = $parameters[1];
        $this->assertEquals('id2', $titleParameter->getName());
        $this->assertEquals('test', $titleParameter->getValue());
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage The entity identifier must be an array because the entity
     * "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity" has composite primary key.
     */
    public function testProcessOnCompositeIdEntityWithGivenScalarId()
    {
        $this->assertFalse($this->context->hasQuery());
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity');
        $this->context->setId(54);

        $this->processor->process($this->context);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage The entity identifier array must have the key "title" because the
     * entity "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity" has composite primary key.
     */
    public function testProcessOnCompositeIdEntityWithGivenWrongId()
    {
        $this->assertFalse($this->context->hasQuery());
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity');
        $this->context->setId(['id' => 45]);

        $this->processor->process($this->context);
    }
}
