<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Writer\Stub\EntityStub;
use Oro\Bundle\ImportExportBundle\Writer\EntityDetachFixer;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EntityDetachFixerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    protected $entityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldHelper */
    protected $fieldHelper;

    /** @var EntityDetachFixer */
    protected $fixer;

    protected function setUp()
    {
        $this->entityManager = $this->createMock('Doctrine\ORM\EntityManager');

        $this->doctrineHelper = $this->createMock('Oro\Bundle\EntityBundle\ORM\DoctrineHelper');
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($this->entityManager));

        $this->fieldHelper = $this->createMock('Oro\Bundle\EntityBundle\Helper\FieldHelper');

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
        $entity = new EntityStub();
        $entity->setReadable($fieldValue);

        if ($fieldValue instanceof ArrayCollection) {
            $linkedEntity = $fieldValue->getIterator()->offsetGet(0);
        } else {
            $linkedEntity = $fieldValue;
        }

        $this->fieldHelper->expects($this->once())
            ->method('getRelations')
            ->with(get_class($entity))
            ->willReturn(
                [
                    ['name' => 'readable'],
                    ['name' => 'notReadable']
                ]
            );

        $metadata = $this->createMock('Doctrine\ORM\Mapping\ClassMetadata');
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($linkedEntity)
            ->will($this->returnValue('id'));

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('getEntityState')
            ->with($linkedEntity)
            ->willReturn(UnitOfWork::STATE_DETACHED);

        $this->entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $this->entityManager->expects($this->once())
            ->method('getReference')
            ->with('stdClass', 'id')
            ->willReturnCallback(
                function () use ($entity) {
                    $entity->reloaded = true;
                    return $entity;
                }
            );
        $this->fixer->fixEntityAssociationFields($entity, 0);
        if ($fieldValue instanceof ArrayCollection) {
            $this->assertTrue($entity->getReadable()->getIterator()->offsetGet(0)->reloaded);
        } else {
            $this->assertTrue($entity->getReadable()->reloaded);
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
