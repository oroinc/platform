<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\RecognizeAssociationType;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class RecognizeAssociationTypeTest extends GetSubresourceProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $subresourcesProvider;

    /** @var RecognizeAssociationType */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->subresourcesProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\SubresourcesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new RecognizeAssociationType(
            $this->subresourcesProvider
        );
    }

    public function testProcessWhenEntityClassNameIsAlreadySet()
    {
        $this->subresourcesProvider->expects($this->never())
            ->method('getSubresources');

        $this->context->setClassName('Test\Class');
        $this->processor->process($this->context);
    }

    public function testProcessWhenAssociationNameIsNotSet()
    {
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError(
                    'relationship constraint',
                    'The association name must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForUnknownAssociation()
    {
        $parentEntityClass = 'Test\ParentClass';
        $associationName = 'testAssociation';

        $entitySubresources = new ApiResourceSubresources($parentEntityClass);

        $this->subresourcesProvider->expects($this->once())
            ->method('getSubresources')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn($entitySubresources);

        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError(
                    'relationship constraint',
                    'The target entity type cannot be recognized.'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForKnownAssociation()
    {
        $parentEntityClass = 'Test\ParentClass';
        $associationName = 'testAssociation';

        $entitySubresources = new ApiResourceSubresources($parentEntityClass);
        $associationSubresource = new ApiSubresource();
        $associationSubresource->setIsCollection(true);
        $associationSubresource->setTargetClassName('Test\Class');
        $entitySubresources->addSubresource($associationName, $associationSubresource);

        $this->subresourcesProvider->expects($this->once())
            ->method('getSubresources')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn($entitySubresources);

        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->processor->process($this->context);

        $this->assertEquals(
            $associationSubresource->getTargetClassName(),
            $this->context->getClassName()
        );
        $this->assertEquals(
            $associationSubresource->isCollection(),
            $this->context->isCollection()
        );
    }
}
