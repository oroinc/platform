<?php


namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\GetList\JsonApi\BuildJsonApiDocument;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class BuildJsonApiDocumentTest extends GetListProcessorTestCase
{
    /** @var BuildJsonApiDocument */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $documentBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $documentBuilderFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->documentBuilder        = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->documentBuilderFactory = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilderFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new BuildJsonApiDocument($this->documentBuilderFactory);
    }

    public function testProcessContextWithoutErrorsOnEmptyResult()
    {
        $this->documentBuilder->expects($this->once())
            ->method('setDataCollection')
            ->with(null);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilderFactory->expects($this->once())
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);

        $this->context->setResult(null);
        $this->processor->process($this->context);
    }

    public function testProcessContextWithoutErrorsOnNonEmptyResult()
    {
        $result   = [new \stdClass()];
        $metadata = new EntityMetadata();

        $this->documentBuilder->expects($this->once())
            ->method('setDataCollection')
            ->with($result, $metadata);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilderFactory->expects($this->once())
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);

        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
    }

    public function testProcessWithErrors()
    {
        $error = new Error();

        $this->documentBuilder->expects($this->never())
            ->method('setDataCollection');
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilder->expects($this->once())
            ->method('setErrorCollection')
            ->with([$error]);
        $this->documentBuilderFactory->expects($this->once())
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);
        $config = new Config();
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->context->addError($error);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessWithException()
    {
        $exception = new \LogicException();

        $this->documentBuilder->expects($this->once())
            ->method('setDataCollection')
            ->willThrowException($exception);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilder->expects($this->once())
            ->method('setErrorObject');
        $this->documentBuilderFactory->expects($this->exactly(2))
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);
        $config = new Config();
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->context->setResult(null);
        $this->processor->process($this->context);

        $this->assertEquals(500, $this->context->getResponseStatusCode());
    }
}
