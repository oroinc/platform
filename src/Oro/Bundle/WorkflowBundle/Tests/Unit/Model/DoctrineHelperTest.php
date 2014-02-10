<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\Proxy;

use Oro\Bundle\WorkflowBundle\Model\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\ItemStub;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\__CG__\ItemStubProxy;

class DoctrineHelperTest extends \PHPUnit_Framework_TestCase
{
    const TEST_IDENTIFIER = 42;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->doctrineHelper = new DoctrineHelper($this->registry);
    }

    protected function tearDown()
    {
        unset($this->registry);
        unset($this->doctrineHelper);
    }

    /**
     * @param string $entity
     * @param string $expectedClass
     * @dataProvider getEntityClassDataProvider
     */
    public function testGetEntityClass($entity, $expectedClass)
    {
        $this->assertEquals($expectedClass, $this->doctrineHelper->getEntityClass($entity));
    }

    /**
     * @return array
     */
    public function getEntityClassDataProvider()
    {
        return array(
            'existing entity' => array(
                'entity'        => new ItemStub(),
                'expectedClass' => 'Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\ItemStub',
            ),
            'entity proxy' => array(
                'entity'        => new ItemStubProxy(),
                'expectedClass' => 'ItemStubProxy',
            ),
        );
    }

    /**
     * @param object $entity
     * @param string $class
     * @dataProvider getEntityIdentifierDataProvider
     */
    public function testGetEntityIdentifier($entity, $class)
    {
        $identifierArray = array('id' => self::TEST_IDENTIFIER);

        $entityManager = $this->getEntityManagerMockForEntityAndClass($entity, $class, $identifierArray);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($class)
            ->will($this->returnValue($entityManager));

        $this->assertEquals($identifierArray, $this->doctrineHelper->getEntityIdentifier($entity));
    }

    public function testGetEntityIdentifierNotManageableEntity()
    {
        $entity = $this->getMock('FooEntity');

        $this->setExpectedException(
            'Oro\Bundle\WorkflowBundle\Exception\NotManageableEntityException',
            sprintf('Entity class "%s" is not manageable', get_class($entity))
        );

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(get_class($entity))
            ->will($this->returnValue(null));

        $this->doctrineHelper->getEntityIdentifier($entity);
    }

    /**
     * @return array
     */
    public function getEntityIdentifierDataProvider()
    {
        return array(
            'existing entity' => array(
                'entity' => new ItemStub(),
                'class'  => 'Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\ItemStub',
            ),
            'entity proxy' => array(
                'entity' => new ItemStubProxy(),
                'class'  => 'ItemStubProxy',
            ),
        );
    }

    /**
     * @param $expected
     * @param array $actual
     * @param bool $exception
     * @dataProvider getSingleEntityIdentifierDataProvider
     */
    public function testGetSingleEntityIdentifier($expected, array $actual, $exception = true)
    {
        $entity = new ItemStubProxy();
        $class = 'ItemStubProxy';

        $entityManager = $this->getEntityManagerMockForEntityAndClass($entity, $class, $actual);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($class)
            ->will($this->returnValue($entityManager));

        $this->assertEquals($expected, $this->doctrineHelper->getSingleEntityIdentifier($entity, $exception));
    }

    public function getSingleEntityIdentifierDataProvider()
    {
        return array(
            'valid identifier' => array(
                'expected' => self::TEST_IDENTIFIER,
                'actual' => array('id' => self::TEST_IDENTIFIER),
            ),
            'empty identifier, no exception' => array(
                'expected' => null,
                'actual' => array('first_id' => 1, 'second_id'),
                'exception' => false,
            ),
            'multiple identifier, no exception' => array(
                'expected' => null,
                'actual' => array('first_id' => 1, 'second_id'),
                'exception' => false,
            ),
        );
    }

    /**
     * @return array
     */
    public function getSingleEntityIdentifierIncorrectIdentifierDataProvider()
    {
        return array(
            'empty identifier' => array(
                'identifier' => array(),
            ),
            'multiple identifier' => array(
                'identifier' => array('key1' => 'value1', 'key2' => 'value2'),
            ),
        );
    }

    /**
     * @param array $identifier
     * @dataProvider getSingleEntityIdentifierIncorrectIdentifierDataProvider
     *
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Can't get single identifier for the entity
     */
    public function testGetSingleEntityIdentifierIncorrectIdentifier(array $identifier)
    {
        $entity = new ItemStubProxy();
        $class = 'ItemStubProxy';

        $entityManager = $this->getEntityManagerMockForEntityAndClass($entity, $class, $identifier);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($class)
            ->will($this->returnValue($entityManager));

        $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * @param object $entity
     * @param boolean $manageable
     * @dataProvider isManageableEntityDataProvider
     */
    public function testIsManageableEntity($entity, $manageable)
    {
        if ($manageable) {
            $entityManager = $this->getMockBuilder('Doctrine\Orm\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();
            $this->registry->expects($this->once())
                ->method('getManagerForClass')
                ->with($this->doctrineHelper->getEntityClass($entity))
                ->will($this->returnValue($entityManager));
        } else {
            $this->registry->expects($this->once())
                ->method('getManagerForClass')
                ->with($this->doctrineHelper->getEntityClass($entity))
                ->will($this->returnValue(null));
        }

        $this->assertEquals($manageable, $this->doctrineHelper->isManageableEntity($entity));
    }

    /**
     * @return array
     */
    public function isManageableEntityDataProvider()
    {
        return array(
            'manageable entity' => array(
                'entity'     => new ItemStubProxy(),
                'manageable' => true
            ),
            'not manageable entity' => array(
                'entity'     => new \DateTime('now'),
                'manageable' => false
            ),
        );
    }

    public function testGetEntityReference()
    {
        $expectedResult = $this->getMock('MockEntityReference');
        $entityClass = 'MockEntity';
        $entityId = 100;

        $entityManager = $this->getMockBuilder('Doctrine\Orm\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getReference'))
            ->getMock();

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())->method('getReference')
            ->with($entityClass, $entityId)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            $this->doctrineHelper->getEntityReference($entityClass, $entityId)
        );
    }

    /**
     * @param object $entity
     * @param string $class
     * @param mixed $identifier
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEntityManagerMockForEntityAndClass($entity, $class, $identifier)
    {
        $entityManager = $this->getMockBuilder('Doctrine\Orm\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getClassMetadata'))
            ->getMock();

        $classMetadata = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->will($this->returnValue($identifier));

        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($class)
            ->will($this->returnValue($classMetadata));

        return $entityManager;
    }
}
