<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Api\Processor\Config;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValidationHelper;
use Oro\Bundle\OrganizationBundle\Api\Processor\Config\AddOwnerValidator;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

class AddOwnerValidatorTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface */
    private $ownershipMetadataProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ValidationHelper */
    private $validationHelper;

    /** @var AddOwnerValidator */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->validationHelper = $this->createMock(ValidationHelper::class);

        $this->processor = new AddOwnerValidator(
            $this->doctrineHelper,
            $this->ownershipMetadataProvider,
            $this->validationHelper
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->ownershipMetadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->processor->process($this->context);
    }

    public function testProcessWithOwnerField()
    {
        $config = [
            'fields' => [
                'owner' => null
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->context->setTargetAction(ApiActions::UPDATE);
        $this->processor->process($this->context);

        self::assertEquals(
            ['constraints' => [new Owner(['groups' => ['api']])]],
            $configObject->getFormOptions()
        );
    }

    public function testProcessForExcludedOwnerField()
    {
        $config = [
            'fields' => [
                'owner' => ['exclude' => true]
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->context->setTargetAction(ApiActions::UPDATE);
        $this->processor->process($this->context);

        self::assertNull($configObject->getFormOptions());
    }

    public function testProcessForRenamedOwnerField()
    {
        $config = [
            'fields' => [
                'owner1' => ['property_path' => 'owner']
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertEquals(
            ['constraints' => [new Owner(['groups' => ['api']])]],
            $configObject->getFormOptions()
        );
    }

    public function testProcessWithoutOwnerField()
    {
        $config = [
            'fields' => [
                'someField' => null
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertEmpty($configObject->getFormOptions());
    }

    public function testProcessWhenConstraintAlreadyExists()
    {
        $config = [
            'fields' => [
                'owner' => null
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);
        $this->validationHelper->expects(self::once())
            ->method('hasValidationConstraintForClass')
            ->with(self::TEST_CLASS_NAME, Owner::class)
            ->willReturn(true);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertNull($configObject->getFormOptions());
        self::assertNull($configObject->getField('owner')->getFormOptions());
    }

    public function testProcessWhenConstraintAlreadyExistsForRenamedOwnerField()
    {
        $config = [
            'fields' => [
                'owner1' => ['property_path' => 'owner']
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);
        $this->validationHelper->expects(self::once())
            ->method('hasValidationConstraintForClass')
            ->with(self::TEST_CLASS_NAME, Owner::class)
            ->willReturn(true);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertNull($configObject->getFormOptions());
        self::assertNull($configObject->getField('owner1')->getFormOptions());
    }
}
