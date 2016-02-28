<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\Get\JsonApi\BuildJsonApiDocument;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildJsonApiDocumentTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProcessorInterface */
    protected $processor;

    /** @var Context */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $documentBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $documentBuilderFactory;

    protected $setDataFunctionName;

    protected function setUp()
    {
        $this->documentBuilder = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->documentBuilderFactory = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilderFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = $this->getProcessor();
        $this->context = $this->getContext();
        $this->setDataFunctionName = $this->getSetterDataFunctionName();
    }

    public function testProcessContextWithoutErrorsOnEmptyResult()
    {
        $this->context->setResult(null);
        $this->documentBuilder->expects($this->once())
            ->method($this->setDataFunctionName)
            ->with(null);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilderFactory->expects($this->once())
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);

        $this->processor->process($this->context);
    }

    public function testProcessContextWithoutErrorsOnNonEmptyResult()
    {
        $result = [new \stdClass()];
        $this->context->setResult($result);
        $metadata = new EntityMetadata();
        $this->context->setMetadata($metadata);
        $this->documentBuilder->expects($this->once())
            ->method($this->setDataFunctionName)
            ->with($result, $metadata);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilderFactory->expects($this->once())
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);

        $this->processor->process($this->context);
    }

    public function testProcessWithErrors()
    {
        $error = new Error();
        $this->context->addError($error);
        $this->documentBuilder->expects($this->never())
            ->method($this->setDataFunctionName);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilder->expects($this->once())
            ->method('setErrorCollection')
            ->with([$error]);
        $this->documentBuilderFactory->expects($this->once())
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);

        $this->assertTrue($this->context->hasErrors());
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessWithException()
    {
        $exception = new \LogicException();
        $this->context->setResult(null);
        $this->documentBuilder->expects($this->once())
            ->method($this->setDataFunctionName)
            ->willThrowException($exception);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilder->expects($this->once())
            ->method('setErrorObject');
        $this->documentBuilderFactory->expects($this->exactly(2))
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);

        $this->processor->process($this->context);
        $this->assertEquals(500, $this->context->getResponseStatusCode());
    }

    /**
     * @return ProcessorInterface
     */
    protected function getProcessor()
    {
        return new BuildJsonApiDocument($this->documentBuilderFactory);
    }

    /**
     * @return Context
     */
    protected function getContext()
    {
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        return new GetContext($configProvider, $metadataProvider);
    }

    /**
     * @return string
     */
    protected function getSetterDataFunctionName()
    {
        return 'setDataObject';
    }
}
