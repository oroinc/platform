<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\EventListener\EntityExtendedStructureOptionsListener;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class EntityExtendedStructureOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    const CURRENT_RELATION_TYPE = 'CurrentType';

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityExtendedStructureOptionsListener */
    protected $listener;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->listener = new EntityExtendedStructureOptionsListener($this->doctrineHelper);
    }

    /**
     * @param string $expectedClass
     * @param string $expectedFieldName
     * @param string $expectedRelationType
     * @param string $fieldName
     * @param string $type
     * @param bool $hasAssociation
     *
     * @dataProvider dataProvider
     */
    public function testOnOptionsRequest(
        $expectedClass,
        $expectedFieldName,
        $expectedRelationType,
        $fieldName,
        $type,
        $hasAssociation
    ) {

        $field = $this->createMock(EntityFieldStructure::class);
        $field->expects($this->once())
            ->method('getName')
            ->willReturn($fieldName);
        $field->expects($this->once())
            ->method('setRelationType')
            ->with($expectedRelationType);
        $field->expects($this->any())
            ->method('getRelationType')
            ->willReturn(self::CURRENT_RELATION_TYPE);

        $data = $this->createMock(EntityStructure::class);
        $data->expects($this->once())
            ->method('getClassName')
            ->willReturn(\stdClass::class);
        $data->expects($this->once())
            ->method('getFields')
            ->willReturn([$field]);

        $entityMetadata = $this->createMock(ClassMetadata::class);

        $entityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with($expectedFieldName)
            ->willReturn($hasAssociation);

        $entityMetadata->expects($this->exactly((int)$hasAssociation))
            ->method('getAssociationMapping')
            ->with($expectedFieldName)
            ->willReturn(['type' => $type]);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityMetadata')
            ->with($expectedClass)
            ->willReturn($entityMetadata);

        $event = $this->createMock(EntityStructureOptionsEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn([$data]);

        $event->expects($this->once())
            ->method('setData')
            ->with([$data])
            ->willReturn($event);

        $this->listener->onOptionsRequest($event);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        $processedRelationType = lcfirst(self::CURRENT_RELATION_TYPE);

        return [
            'no association' => [
                'expectedClass' => \stdClass::class,
                'expectedFieldName' => 'SimpleField',
                'expectedRelationType' => $processedRelationType,
                'fieldName' => 'SimpleField',
                'type' => 'simpletype',
                'hasAssociation' => false
            ],
            'custom field' => [
                'expectedClass' => 'OtherClass',
                'expectedFieldName' => 'SimpleField',
                'expectedRelationType' => $processedRelationType,
                'fieldName' => 'OtherClass::SimpleField',
                'type' => 'simpletype',
                'hasAssociation' => false
            ],
            'not supported relation' => [
                'expectedClass' => 'OtherClass',
                'expectedFieldName' => 'SimpleField',
                'expectedRelationType' => $processedRelationType,
                'fieldName' => 'OtherClass::SimpleField',
                'type' => 'SimpleType',
                'hasAssociation' => true
            ],
            'ref-one case' => [
                'expectedClass' => 'OtherClass',
                'expectedFieldName' => 'SimpleField',
                'expectedRelationType' => $processedRelationType,
                'fieldName' => 'OtherClass::SimpleField',
                'type' => ClassMetadata::MANY_TO_ONE,
                'hasAssociation' => false
            ],
            'ref-many case' => [
                'expectedClass' => 'OtherClass',
                'expectedFieldName' => 'SimpleField',
                'expectedRelationType' => $processedRelationType,
                'fieldName' => 'OtherClass::SimpleField',
                'type' => ClassMetadata::MANY_TO_MANY,
                'hasAssociation' => false
            ],
            'with association' => [
                'expectedClass' => 'OtherClass',
                'expectedFieldName' => 'SimpleField',
                'expectedRelationType' => RelationType::MANY_TO_MANY,
                'fieldName' => 'OtherClass::SimpleField',
                'type' => ClassMetadata::MANY_TO_MANY,
                'hasAssociation' => true
            ],
        ];
    }
}
