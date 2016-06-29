<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildSingleItemQuery;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorOrmRelatedTestCase;

class BuildSingleItemQueryTest extends GetProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $criteriaConnector;

    /** @var BuildSingleItemQuery */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->criteriaConnector = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\CriteriaConnector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new BuildSingleItemQuery($this->doctrineHelper, $this->criteriaConnector);
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

        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    public function testProcessForSingleIdEntity()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User';
        $id        = 12;

        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $this->criteriaConnector->expects($this->once())
            ->method('applyCriteria');

        $this->context->setCriteria($criteria);
        $this->context->setClassName($className);
        $this->context->setId($id);
        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        $this->assertEquals(
            sprintf('SELECT e FROM %s e WHERE e.id = :id', $className),
            $query->getDQL()
        );
        /** @var Parameter $parameter */
        $parameter = $query->getParameters()->first();
        $this->assertEquals('id', $parameter->getName());
        $this->assertEquals($id, $parameter->getValue());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The entity identifier cannot be an array because the entity "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User" has single primary key.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessForSingleIdEntityWithGivenArrayId()
    {
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setCriteria(new Criteria($resolver));
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->context->setId([2, 4]);
        $this->processor->process($this->context);
    }

    public function testProcessForCompositeIdEntity()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity';
        $id        = ['id' => 23, 'title' => 'test'];

        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setCriteria(new Criteria($resolver));
        $this->context->setClassName($className);
        $this->context->setId($id);
        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasQuery());
        /** @var QueryBuilder $query */
        $query = $this->context->getQuery();
        $this->assertEquals(
            sprintf('SELECT e FROM %s e WHERE e.id = :id1 AND e.title = :id2', $className),
            $query->getDQL()
        );

        /** @var Parameter $parameter */
        $parameters  = $query->getParameters();
        $idParameter = $parameters[0];
        $this->assertEquals('id1', $idParameter->getName());
        $this->assertEquals($id['id'], $idParameter->getValue());
        $titleParameter = $parameters[1];
        $this->assertEquals('id2', $titleParameter->getName());
        $this->assertEquals($id['title'], $titleParameter->getValue());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The entity identifier must be an array because the entity "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity" has composite primary key.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessForCompositeIdEntityWithGivenScalarId()
    {
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setCriteria(new Criteria($resolver));
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity');
        $this->context->setId(54);
        $this->processor->process($this->context);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The entity identifier array must have the key "title" because the entity "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity" has composite primary key.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessForCompositeIdEntityWithGivenWrongId()
    {
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setCriteria(new Criteria($resolver));
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity');
        $this->context->setId(['id' => 45]);
        $this->processor->process($this->context);
    }
}
