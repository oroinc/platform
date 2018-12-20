<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Entity\Manager\Field;

use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldManager;
use Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException;

class EntityFieldManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var EntityFieldManager */
    protected $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $handler;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $formBuilder;

    /**  @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityRoutingHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $ownershipMetadataProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
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
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface')
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
            'isOrganizationOwned' => false,
            'getOwnerFieldName' => 'owner'
        ]);
        $this->ownershipMetadataProvider->expects($this->any())->method('getMetaData')->willReturn($metaDataOwnerShip);

        $this->manager->update($this->getEntity(), [
            'firstName' => 'Test'
        ]);
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException
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
            'isOrganizationOwned' => false,
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
        $metadataConfig = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface');

        $metadataConfig
            ->expects($this->any())->method('hasOwner')
            ->willReturn($options['hasOwner']);

        $metadataConfig->expects($this->any())
            ->method('isOrganizationOwned')
            ->willReturn($options['isOrganizationOwned']);

        $metadataConfig->expects($this->any())
            ->method('getOwnerFieldName')
            ->willReturn($options['getOwnerFieldName']);

        return $metadataConfig;
    }

    /**
     * @param $options
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMetadata($options)
    {
        $metadata = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata')
            ->setMethods([
                'hasField',
                'isOrganizationOwned',
                'getOwnerFieldName',
                'getFieldMapping',
                'hasAssociation'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        if (array_key_exists('getFieldMapping', $options)) {
            $metadata->expects($this->any())
                ->method('hasField')
                ->willReturn($options['getFieldMapping']);
        }

        if (array_key_exists('hasField', $options)) {
            $metadata->expects($this->any())
                ->method('hasField')
                ->willReturn($options['hasField']);
        }

        if (array_key_exists('hasAssociation', $options)) {
            $metadata->expects($this->any())
                ->method('hasField')
                ->willReturn($options['hasAssociation']);
        }

        if (array_key_exists('isOrganizationOwned', $options)) {
            $metadata->expects($this->any())
                ->method('isOrganizationOwned')
                ->willReturn($options['isOrganizationOwned']);
        }

        if (array_key_exists('getOwnerFieldName', $options)) {
            $metadata->expects($this->any())
                ->method('getOwnerFieldName')
                ->willReturn($options['getOwnerFieldName']);
        }

        return $metadata;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
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
