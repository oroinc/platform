<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ImportExportBundle\Writer\EntityDetachFixer;

class EntityDetachFixerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityFieldProvider */
    protected $entityFieldProvider;

    /** @var EntityDetachFixer */
    protected $fixer;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($this->entityManager);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityFieldProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->fixer = new EntityDetachFixer($this->registry, $this->entityFieldProvider);
    }

    public function testFixEntityAssociationFieldsLevel()
    {
        $entity = new \stdClass();

        $this->entityFieldProvider->expects($this->never())
            ->method('getRelations');
        $this->fixer->fixEntityAssociationFields($entity, -1);
    }

    /**
     * @dataProvider valueDataProvider
     * @param mixed $fieldValue
     */
    public function testFixEntityAssociationFieldsEntity($fieldValue)
    {
        $entity = new \stdClass();
        $entity->field = $fieldValue;

        if ($fieldValue instanceof ArrayCollection) {
            $linkedEntity = $fieldValue->getIterator()->offsetGet(0);
        } else {
            $linkedEntity = $fieldValue;
        }

        $this->entityFieldProvider->expects($this->once())
            ->method('getRelations')
            ->with(get_class($entity))
            ->will($this->returnValue([['name' => 'field']]));

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($linkedEntity)
            ->will($this->returnValue('id'));

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($entity))
            ->will($this->returnValue($metadata));

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('getEntityState')
            ->with($linkedEntity)
            ->will($this->returnValue(UnitOfWork::STATE_DETACHED));

        $this->entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(get_class($entity), 'id')
            ->will(
                $this->returnCallback(
                    function () use ($entity) {
                        $entity->reloaded = true;
                        return $entity;
                    }
                )
            );
        $this->fixer->fixEntityAssociationFields($entity, 0);
        if ($fieldValue instanceof ArrayCollection) {
            $this->assertTrue($entity->field->getIterator()->offsetGet(0)->reloaded);
        } else {
            $this->assertTrue($entity->field->reloaded);
        }
    }

    /**
     * @return array
     */
    public function valueDataProvider()
    {
        $entity = new \stdClass();
        $collection = new ArrayCollection([$entity]);

        return [[new \stdClass()], [$collection]];
    }
}
