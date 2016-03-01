<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Extension\InlineEditing\Processor;

use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldManager;
use Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException;

class EntityManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var EntityFieldManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $handler;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $formBuilder;

    /**  @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityRoutingHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $ownershipMetadataProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $validator;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()->getMock();

        $this->formBuilder = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Form\EntityField\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Form\EntityField\Handler\EntityApiBaseHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRoutingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ownershipMetadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldValidator')
            ->disableOriginalConstructor()
            ->getMock();



        $this->manager = new EntityFieldManager(
            $this->registry,
            $this->formBuilder,
            $this->handler,
            $this->entityRoutingHelper,
            $this->ownershipMetadataProvider,
            $this->validator
        );
    }

    public function testUpdate()
    {
        $this->initForm([
            'build' => [
                'expected' => $this->once()
            ]
        ]);

        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMetadata([
            'hasField' => true,
            'hasAssociation' => false,
            'getFieldMapping' => ['type' => 'boolean']
        ]);
        $entityManager->expects($this->any())->method('getClassMetadata')->willReturn($metadata);
        $this->registry->expects($this->any())->method('getManager')->willReturn($entityManager);

        $metaDataOwnerShip = $this->getMetaDataOwnerShip([
            'hasOwner' => true,
            'isGlobalLevelOwned' => false,
            'getOwnerFieldName' => 'owner'
        ]);
        $this->ownershipMetadataProvider->expects($this->any())->method('getMetaData')->willReturn($metaDataOwnerShip);

        $this->manager->update($this->getEntity(), [
            'firstName' => 'Test'
        ]);
    }

    /**
     * @expectedException Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException
     */
    public function testBlockedFieldNameUpdate()
    {
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->getMetadata([
            'hasField' => true,
            'hasAssociation' => false,
            'getFieldMapping' => ['type' => 'boolean']
        ]);
        $entityManager->expects($this->any())->method('getClassMetadata')->willReturn($metadata);
        $this->registry->expects($this->any())->method('getManager')->willReturn($entityManager);

        $metaDataOwnerShip = $this->getMetaDataOwnerShip([
            'hasOwner' => true,
            'isGlobalLevelOwned' => false,
            'getOwnerFieldName' => 'owner'
        ]);
        $this->ownershipMetadataProvider->expects($this->any())->method('getMetaData')->willReturn($metaDataOwnerShip);

        $this->validator->expects($this->once())->method('validate')
            ->will($this->throwException(new FieldUpdateAccessException()));

        $this->manager->update($this->getEntity(), [
            'id' => 10,
            'updatedAt' => 10,
            'createdAt' => 10
        ]);
    }

    protected function initForm($options)
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')->disableOriginalConstructor()->getMock();
        $this->formBuilder->expects($options['build']['expected'])->method('build')->willReturn($form);
    }

    protected function getMetaDataOwnerShip($options)
    {
        $metadataConfig = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface')
            ->setMethods([
                'hasOwner',
                'isGlobalLevelOwned',
                'getOwnerFieldName',
                'hasField',
                'getOwnerType',
                'isBasicLevelOwned',
                'isLocalLevelOwned',
                'isSystemLevelOwned',
                'getOwnerColumnName',
                'getGlobalOwnerColumnName',
                'getGlobalOwnerFieldName',
                'getAccessLevelNames'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $metadataConfig
            ->expects($this->any())->method('hasOwner')
            ->willReturn($options['hasOwner']);

        $metadataConfig->expects($this->any())
            ->method('isGlobalLevelOwned')
            ->willReturn($options['isGlobalLevelOwned']);

        $metadataConfig->expects($this->any())
            ->method('getOwnerFieldName')
            ->willReturn($options['getOwnerFieldName']);

        return $metadataConfig;
    }

    /**
     * @param $options
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMetadata($options)
    {
        $metadata = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata')
            ->setMethods([
                'hasField',
                'isGlobalLevelOwned',
                'getOwnerFieldName',
                'getFieldMapping',
                'hasAssociation'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        if (array_key_exists('getFieldMapping', $options)) {
            $metadata->expects($this->any())->method('hasField')->willReturn($options['getFieldMapping']);
        }

        if (array_key_exists('hasField', $options)) {
            $metadata->expects($this->any())->method('hasField')->willReturn($options['hasField']);
        }

        if (array_key_exists('hasAssociation', $options)) {
            $metadata->expects($this->any())->method('hasField')->willReturn($options['hasAssociation']);
        }

        if (array_key_exists('isGlobalLevelOwned', $options)) {
            $metadata->expects($this->any())->method('isGlobalLevelOwned')->willReturn($options['isGlobalLevelOwned']);
        }

        if (array_key_exists('getOwnerFieldName', $options)) {
            $metadata->expects($this->any())->method('getOwnerFieldName')->willReturn($options['getOwnerFieldName']);
        }

        return $metadata;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEntity()
    {
        $businessUnit = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
            ->disableOriginalConstructor()
            ->getMock();

        $businessUnit->expects($this->any())->method('getId')->willReturn(1);

        $entity = $this->getMockBuilder('\Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->any())->method('getOwner')->willReturn($businessUnit);
        return $entity;
    }
}
