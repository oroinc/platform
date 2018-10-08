<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\EventListener\EntityExtendedStructureOptionsListener;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Component\Testing\Unit\EntityTrait;

class EntityExtendedStructureOptionsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const CURRENT_RELATION_TYPE = 'CurrentType';

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
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
            ->method('isManageableEntity')
            ->with($expectedClass)
            ->willReturn(true);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityMetadata')
            ->with($expectedClass)
            ->willReturn($entityMetadata);

        $event = new EntityStructureOptionsEvent();
        $event->setData([$this->getEntityStructure($fieldName, self::CURRENT_RELATION_TYPE)]);

        $this->listener->onOptionsRequest($event);

        $this->assertEquals([$this->getEntityStructure($fieldName, $expectedRelationType)], $event->getData());
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

    public function testOnOptionsRequestForNotManageableEntity()
    {
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects($this->never())
            ->method('hasAssociation')
            ->with('field1')
            ->willReturn(false);

        $entityMetadata->expects($this->never())
            ->method('getAssociationMapping');

        $this->doctrineHelper
            ->expects($this->once())
            ->method('isManageableEntity')
            ->with(\stdClass::class)
            ->willReturn(false);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityMetadata');

        $event = new EntityStructureOptionsEvent();
        $event->setData([$this->getEntityStructure('field1', self::CURRENT_RELATION_TYPE)]);

        $this->listener->onOptionsRequest($event);

        $this->assertEquals([$this->getEntityStructure('field1', 'currentType')], $event->getData());
    }

    /**
     * @param string $fieldName
     * @param string $relationType
     * @return EntityStructure
     */
    protected function getEntityStructure($fieldName, $relationType)
    {
        return $this->getEntity(
            EntityStructure::class,
            [
                'className' => \stdClass::class,
                'fields' => [
                    $this->getEntity(
                        EntityFieldStructure::class,
                        [
                            'name' => $fieldName,
                            'relationType' => $relationType
                        ]
                    )
                ]
            ]
        );
    }
}
