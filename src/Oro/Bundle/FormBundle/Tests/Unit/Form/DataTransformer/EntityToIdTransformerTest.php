<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\MappingException;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;

class EntityToIdTransformerTest extends \PHPUnit\Framework\TestCase
{
    protected $entityManager;

    protected $repository;

    /**
     * @dataProvider transformDataProvider
     *
     * @param string $property
     * @param mixed $value
     * @param mixed $expectedValue
     */
    public function testTransform($property, $value, $expectedValue)
    {
        $transformer = new EntityToIdTransformer($this->getMockEntityManager(), 'TestClass', $property, null);
        $this->assertEquals($expectedValue, $transformer->transform($value));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return array(
            'default' => array(
                'id',
                $this->createMockEntity('id', 1),
                1
            ),
            'empty' => array(
                'id',
                null,
                null
            ),
        );
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessageExpected Expected argument of type "array", "string" given
     */
    public function testTransformFailsWhenValueInNotAnArray()
    {
        $transformer = new EntityToIdTransformer($this->getMockEntityManager(), 'TestClass', 'id', null);
        $transformer->transform('invalid value');
    }

    public function testReverseTransformEmpty()
    {
        $transformer = new EntityToIdTransformer($this->getMockEntityManager(), 'TestClass', 'id', null);
        $this->assertNull($transformer->reverseTransform(''));
    }

    public function testReverseTransform()
    {
        $entity = $this->createMockEntity('id', 1);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->will($this->returnValue($entity));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with('TestClass')
            ->will($this->returnValue($repository));

        $transformer = new EntityToIdTransformer($em, 'TestClass', 'id', null);
        $this->assertEquals($entity, $transformer->reverseTransform(1));
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage The value "1" does not exist or not unique.
     */
    public function testReverseTransformFailsNotFindEntity()
    {
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->will($this->returnValue(null));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with('TestClass')
            ->will($this->returnValue($repository));

        $transformer = new EntityToIdTransformer($em, 'TestClass', 'id', null);
        $transformer->reverseTransform(1);
    }

    public function testReverseTransformQueryBuilder()
    {
        $entity = $this->createMockEntity('id', 1);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $self= $this;
        $callback = function ($pRepository, $pId) use ($self, $repository, $entity) {
            $self->assertEquals($repository, $pRepository);
            $self->assertEquals(1, $pId);

            $query = $self->getMockBuilder('Doctrine\ORM\AbstractQuery')
                ->disableOriginalConstructor()
                ->setMethods(array('execute'))
                ->getMockForAbstractClass();
            $query->expects($self->once())
                ->method('execute')
                ->will($self->returnValue([$entity]));

            $qb = $self->getMockBuilder('Doctrine\ORM\QueryBuilder')
                ->disableOriginalConstructor()
                ->getMock();
            $qb->expects($self->once())
                ->method('getQuery')
                ->will($self->returnValue($query));

            return $qb;
        };

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with('TestClass')
            ->will($this->returnValue($repository));

        $transformer = new EntityToIdTransformer($em, 'TestClass', 'id', $callback);
        $this->assertEquals($entity, $transformer->reverseTransform(1));
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage The value "1" does not exist or not unique.
     */
    public function testReverseTransformTransformationFailedException()
    {
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $self = $this;

        $callback = function () use ($self) {
            $query = $self->getMockBuilder('Doctrine\ORM\AbstractQuery')
                ->disableOriginalConstructor()
                ->setMethods(array('execute'))
                ->getMockForAbstractClass();
            $query->expects($self->once())
                ->method('execute')
                ->will($self->returnValue([]));

            $qb = $self->getMockBuilder('Doctrine\ORM\QueryBuilder')
                ->disableOriginalConstructor()
                ->getMock();
            $qb->expects($self->once())
                ->method('getQuery')
                ->will($self->returnValue($query));

            return $qb;
        };

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with('TestClass')
            ->will($this->returnValue($repository));

        $transformer = new EntityToIdTransformer($em, 'TestClass', 'id', $callback, true);
        $transformer->reverseTransform(1);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Doctrine\ORM\QueryBuilder", "NULL" given
     */
    public function testReverseTransformQueryBuilderUnexpectedTypeException()
    {
        $entity = $this->createMockEntity('id', 1);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $callback = function () {
            return null;
        };

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with('TestClass')
            ->will($this->returnValue($repository));

        $transformer = new EntityToIdTransformer($em, 'TestClass', 'id', $callback);
        $this->assertEquals($entity, $transformer->reverseTransform(1));
    }

    public function testPropertyConstruction()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));

        new EntityToIdTransformer($em, 'TestClass', null, null);
    }

    /**
     * @expectedException \Oro\Bundle\FormBundle\Form\Exception\FormException
     * @expectedExceptionMessage Cannot get id property path of entity. "TestClass" has composite primary key.
     */
    public function testPropertyConstructionException()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will(
                $this->returnCallback(
                    function () {
                        throw new MappingException('Exception');
                    }
                )
            );
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));

        new EntityToIdTransformer($em, 'TestClass', null, null);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "callable", "string" given
     */
    public function testCallbackException()
    {
        new EntityToIdTransformer($this->getMockEntityManager(), 'TestClass', 'id', 'uncallable');
    }

    /**
     * @return EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockEntityManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->setMethods(array('getClassMetadata', 'getRepository'))
                ->getMockForAbstractClass();
        }

        return $this->entityManager;
    }

    /**
     * Create mock entity by id property name and value
     *
     * @param string $property
     * @param mixed $value
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockEntity($property, $value)
    {
        $getter = 'get' . ucfirst($property);
        $result = $this->getMockBuilder(\stdClass::class)->setMethods(array($getter))->getMock();
        $result->expects($this->any())->method($getter)->will($this->returnValue($value));

        return $result;
    }
}
