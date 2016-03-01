<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ImportExportBundle\Writer\EntityDetachFixer;

class EntityDetachFixerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FieldHelper */
    protected $fieldHelper;

    /** @var EntityDetachFixer */
    protected $fixer;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($this->entityManager));

        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fixer = new EntityDetachFixer(
            $this->doctrineHelper,
            $this->fieldHelper,
            PropertyAccess::createPropertyAccessor()
        );
    }

    public function testFixEntityAssociationFieldsLevel()
    {
        $entity = new \stdClass();

        $this->fieldHelper->expects($this->never())
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

        $this->fieldHelper->expects($this->once())
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
            ->method('getReference')
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
