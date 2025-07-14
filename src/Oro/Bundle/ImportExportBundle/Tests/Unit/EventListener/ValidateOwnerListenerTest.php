<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\EventListener\ValidateOwnerListener;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\TestEntity;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidateOwnerListenerTest extends TestCase
{
    private OwnershipMetadataProviderInterface&MockObject $ownershipMetadataProvider;
    private ConfigurableTableDataConverter&MockObject $configurableDataConverter;
    private ValidateOwnerListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->configurableDataConverter = $this->createMock(ConfigurableTableDataConverter::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return 'translated_' . $key;
            });

        $this->listener = new ValidateOwnerListener(
            $this->ownershipMetadataProvider,
            PropertyAccess::createPropertyAccessor(),
            $this->configurableDataConverter,
            $translator
        );
    }

    public function testOnProcessAfterWithNonACLProtectedEntity(): void
    {
        $entity = new TestEntity();
        $context = new Context([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(TestEntity::class)
            ->willReturn(new OwnershipMetadata());

        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);
        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
    }

    public function testOnProcessAfterWithSystemOwnershipTypeEntity(): void
    {
        $entity = new TestEntity();
        $context = new Context([]);
        $metadata = new OwnershipMetadata('NONE');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);
        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
    }

    public function testOnProcessAfterWithUserOwnershipTypeEntityWithFillerData(): void
    {
        $entity = new TestEntity();
        $entity->setUserOwner(new User());
        $entity->setOrganization(new Organization());
        $context = new Context([]);
        $metadata = new OwnershipMetadata(
            'USER',
            'userOwner',
            'userOwner',
            'organization',
            'organization'
        );

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);
        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
    }

    public function testOnProcessAfterWithUserOwnershipTypeEntityWithoutOwnerData(): void
    {
        $entity = new TestEntity();
        $entity->setOrganization(new Organization());
        $context = new Context([]);
        $metadata = new OwnershipMetadata(
            'USER',
            'userOwner',
            'userOwner',
            'organization',
            'organization'
        );

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->configurableDataConverter->expects(self::once())
            ->method('getFieldHeaderWithRelation')
            ->with(TestEntity::class, 'userOwner')
            ->willReturn('Owner field');

        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);
        $this->listener->onProcessAfter($event);

        self::assertEquals(['Owner field: translated_This value should not be blank.'], $context->getErrors());
    }

    public function testOnProcessAfterWithUserOwnershipTypeEntityWithoutOrganizationData(): void
    {
        $entity = new TestEntity();
        $entity->setUserOwner(new User());
        $context = new Context([]);
        $metadata = new OwnershipMetadata(
            'USER',
            'userOwner',
            'userOwner',
            'organization',
            'organization'
        );

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->configurableDataConverter->expects(self::once())
            ->method('getFieldHeaderWithRelation')
            ->with(TestEntity::class, 'organization')
            ->willReturn('Organization field');

        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);
        $this->listener->onProcessAfter($event);

        self::assertEquals(['Organization field: translated_This value should not be blank.'], $context->getErrors());
    }

    public function testOnProcessAfterWithBusinessUnitOwnershipTypeEntityWithFillerData(): void
    {
        $entity = new TestEntity();
        $entity->setBusinessUnitOwner(new BusinessUnit());
        $entity->setOrganization(new Organization());
        $context = new Context([]);
        $metadata = new OwnershipMetadata(
            'BUSINESS_UNIT',
            'businessUnitOwner',
            'businessUnitOwner',
            'organization',
            'organization'
        );

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);
        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
    }

    public function testOnProcessAfterWithBusinessUnitOwnershipTypeEntityWithoutOwnerData(): void
    {
        $entity = new TestEntity();
        $entity->setOrganization(new Organization());
        $context = new Context([]);
        $metadata = new OwnershipMetadata(
            'BUSINESS_UNIT',
            'businessUnitOwner',
            'businessUnitOwner',
            'organization',
            'organization'
        );

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->configurableDataConverter->expects(self::once())
            ->method('getFieldHeaderWithRelation')
            ->with(TestEntity::class, 'businessUnitOwner')
            ->willReturn('BU owner field');

        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);
        $this->listener->onProcessAfter($event);

        self::assertEquals(['BU owner field: translated_This value should not be blank.'], $context->getErrors());
    }

    public function testOnProcessAfterWithBusinessUnitOwnershipTypeEntityWithoutOrganizationData(): void
    {
        $entity = new TestEntity();
        $entity->setBusinessUnitOwner(new BusinessUnit());
        $context = new Context([]);
        $metadata = new OwnershipMetadata(
            'BUSINESS_UNIT',
            'businessUnitOwner',
            'businessUnitOwner',
            'organization',
            'organization'
        );

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->configurableDataConverter->expects(self::once())
            ->method('getFieldHeaderWithRelation')
            ->with(TestEntity::class, 'organization')
            ->willReturn('Organization field');

        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);
        $this->listener->onProcessAfter($event);

        self::assertEquals(['Organization field: translated_This value should not be blank.'], $context->getErrors());
    }

    public function testOnProcessAfterWithOrganizationOwnershipTypeEntityWithFillerData(): void
    {
        $entity = new TestEntity();
        $entity->setOrganization(new Organization());
        $context = new Context([]);
        $metadata = new OwnershipMetadata(
            'ORGANIZATION',
            'organization',
            'organization'
        );

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);
        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
    }

    public function testOnProcessAfterWithOrganizationOwnershipTypeEntityWithoutOwnerData(): void
    {
        $entity = new TestEntity();
        $context = new Context([]);
        $metadata = new OwnershipMetadata(
            'ORGANIZATION',
            'organization',
            'organization'
        );

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->configurableDataConverter->expects(self::once())
            ->method('getFieldHeaderWithRelation')
            ->with(TestEntity::class, 'organization')
            ->willReturn('Org owner field');

        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);
        $this->listener->onProcessAfter($event);

        self::assertEquals(['Org owner field: translated_This value should not be blank.'], $context->getErrors());
    }
}
