<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\AddOwnerNotNullValidator;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class AddOwnerNotNullValidatorTest extends ConfigProcessorTestCase
{
    /** @var AddOwnerNotNullValidator */
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

        $this->processor = new AddOwnerNotNullValidator($this->doctrineHelper, $this->ownershipMetadataProvider);
    }

    public function testProcessOnNonManageableEntity()
    {
        $className = 'stdClass';
        $this->context->setClassName($className);
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(false);
        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {

    }
}
