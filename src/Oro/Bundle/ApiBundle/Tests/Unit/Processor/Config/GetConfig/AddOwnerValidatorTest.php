<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\AddOwnerValidator;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

class AddOwnerValidatorTest extends ConfigProcessorTestCase
{
    /** @var AddOwnerValidator */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $ownershipMetadataProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $validationHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->ownershipMetadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->validationHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\ValidationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new AddOwnerValidator(
            $this->doctrineHelper,
            $this->ownershipMetadataProvider,
            $this->validationHelper
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $config = [
            'fields' => [
                'owner' => null,
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertEquals(
            ['constraints' => [new Owner()]],
            $configObject->getFormOptions()
        );
        $this->assertEquals(
            ['constraints' => [new NotBlank()]],
            $configObject->getField('owner')->getFormOptions()
        );
    }

    public function testProcessForRenamedOwnerField()
    {
        $config = [
            'fields' => [
                'owner1' => ['property_path' => 'owner'],
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertEquals(
            ['constraints' => [new Owner()]],
            $configObject->getFormOptions()
        );
        $this->assertEquals(
            ['constraints' => [new NotBlank()]],
            $configObject->getField('owner1')->getFormOptions()
        );
    }

    public function testProcessWithoutOwnerField()
    {
        $config = [
            'fields' => [
                'someField' => null,
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertEmpty($configObject->getFormOptions());
    }

    public function testProcessWhenConstraintsAlreadyExist()
    {
        $config = [
            'fields' => [
                'owner' => null,
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);
        $this->validationHelper->expects($this->once())
            ->method('hasValidationConstraintForProperty')
            ->with(
                self::TEST_CLASS_NAME,
                'owner',
                'Symfony\Component\Validator\Constraints\NotBlank'
            )
            ->willReturn(true);
        $this->validationHelper->expects($this->once())
            ->method('hasValidationConstraintForClass')
            ->with(
                self::TEST_CLASS_NAME,
                'Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner'
            )
            ->willReturn(true);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertNull($configObject->getFormOptions());
        $this->assertNull($configObject->getField('owner')->getFormOptions());
    }

    public function testProcessWhenConstraintsAlreadyExistRenamedOwnerField()
    {
        $config = [
            'fields' => [
                'owner1' => ['property_path' => 'owner'],
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);
        $this->validationHelper->expects($this->once())
            ->method('hasValidationConstraintForProperty')
            ->with(
                self::TEST_CLASS_NAME,
                'owner',
                'Symfony\Component\Validator\Constraints\NotBlank'
            )
            ->willReturn(true);
        $this->validationHelper->expects($this->once())
            ->method('hasValidationConstraintForClass')
            ->with(
                self::TEST_CLASS_NAME,
                'Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner'
            )
            ->willReturn(true);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertNull($configObject->getFormOptions());
        $this->assertNull($configObject->getField('owner1')->getFormOptions());
    }
}
