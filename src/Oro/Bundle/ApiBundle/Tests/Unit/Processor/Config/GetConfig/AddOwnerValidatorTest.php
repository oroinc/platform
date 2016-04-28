<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\AddOwnerValidator;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

class AddOwnerValidatorTest extends ConfigProcessorTestCase
{
    /** @var AddOwnerValidator */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $ownershipMetadataProvider;

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

        $this->processor = new AddOwnerValidator($this->doctrineHelper, $this->ownershipMetadataProvider);
    }

    public function testProcessOnNonManageableEntity()
    {
        $className = 'stdClass';
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(false);
        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->context->setClassName($className);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $className = 'stdClass';
        $fieldConfig = new EntityDefinitionFieldConfig();
        $definition = new EntityDefinitionConfig();
        $definition->addField('owner', $fieldConfig);
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(true);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with($className)
            ->willReturn($ownershipMetadata);

        $this->context->setClassName($className);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $formOptions = $fieldConfig->getFormOptions();
        $this->assertEquals(1, count($formOptions));
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\NotBlank', $formOptions['constraints'][0]);
        $entityFormOptions = $definition->getFormOptions();
        $this->assertEquals(1, count($entityFormOptions));
        $this->assertInstanceOf(
            'Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner',
            $entityFormOptions['constraints'][0]
        );
    }

    public function testProcessWithoutOwnerField()
    {
        $className = 'stdClass';
        $fieldConfig = new EntityDefinitionFieldConfig();
        $definition = new EntityDefinitionConfig();
        $definition->addField('nonowner', $fieldConfig);
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with($className)
            ->willReturn($ownershipMetadata);

        $this->context->setClassName($className);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertEmpty($fieldConfig->getFormOptions());
        $this->assertEmpty($definition->getFormOptions());
    }
}
