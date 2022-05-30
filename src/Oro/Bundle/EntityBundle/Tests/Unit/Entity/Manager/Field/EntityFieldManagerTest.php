<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Entity\Manager\Field;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldManager;
use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldValidator;
use Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException;
use Oro\Bundle\EntityBundle\Form\EntityField\FormBuilder;
use Oro\Bundle\EntityBundle\Form\EntityField\Handler\EntityApiBaseHandler;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;

class EntityFieldManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntityApiBaseHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var FormBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $formBuilder;

    /** @var EntityRoutingHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRoutingHelper;

    /** @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ownershipMetadataProvider;

    /** @var EntityFieldValidator|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var EntityFieldManager */
    private $manager;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->formBuilder = $this->createMock(FormBuilder::class);
        $this->handler = $this->createMock(EntityApiBaseHandler::class);
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->validator = $this->createMock(EntityFieldValidator::class);

        $this->manager = new EntityFieldManager(
            $this->doctrine,
            $this->formBuilder,
            $this->handler,
            $this->entityRoutingHelper,
            $this->ownershipMetadataProvider,
            $this->validator
        );
    }

    public function testUpdate()
    {
        $form = $this->createMock(FormInterface::class);
        $this->formBuilder->expects($this->once())
            ->method('build')
            ->willReturn($form);

        $entityManager = $this->createMock(ObjectManager::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasField')
            ->willReturn(true);
        $metadata->expects($this->any())
            ->method('getFieldMapping')
            ->willReturn(['type' => 'boolean']);

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);

        $ownershipMetadata = $this->createMock(OwnershipMetadataInterface::class);
        $ownershipMetadata->expects($this->any())
            ->method('hasOwner')
            ->willReturn(true);
        $ownershipMetadata->expects($this->any())
            ->method('isOrganizationOwned')
            ->willReturn(false);
        $ownershipMetadata->expects($this->any())
            ->method('getOwnerFieldName')
            ->willReturn('owner');
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getMetaData')
            ->willReturn($ownershipMetadata);

        $this->manager->update($this->getEntity(), [
            'firstName' => 'Test'
        ]);
    }

    public function testBlockedFieldNameUpdate()
    {
        $this->expectException(FieldUpdateAccessException::class);
        $entityManager = $this->createMock(ObjectManager::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasField')
            ->willReturn(true);
        $metadata->expects($this->any())
            ->method('getFieldMapping')
            ->willReturn(['type' => 'boolean']);

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);

        $ownershipMetadata = $this->createMock(OwnershipMetadataInterface::class);
        $ownershipMetadata->expects($this->any())
            ->method('hasOwner')
            ->willReturn(true);
        $ownershipMetadata->expects($this->any())
            ->method('isOrganizationOwned')
            ->willReturn(false);
        $ownershipMetadata->expects($this->any())
            ->method('getOwnerFieldName')
            ->willReturn('owner');
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getMetaData')
            ->willReturn($ownershipMetadata);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willThrowException(new FieldUpdateAccessException());

        $this->manager->update($this->getEntity(), [
            'id' => 10,
            'updatedAt' => 10,
            'createdAt' => 10
        ]);
    }

    private function getEntity(): User
    {
        $businessUnit = $this->createMock(BusinessUnit::class);
        $businessUnit->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $entity = $this->createMock(User::class);
        $entity->expects($this->any())
            ->method('getOwner')
            ->willReturn($businessUnit);

        return $entity;
    }
}
