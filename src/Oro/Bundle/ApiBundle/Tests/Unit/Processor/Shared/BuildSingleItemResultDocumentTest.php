<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildSingleItemResultDocument;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class BuildSingleItemResultDocumentTest extends GetProcessorTestCase
{
    /** @var BuildSingleItemResultDocument */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $documentBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $errorCompleter;

    protected function setUp()
    {
        parent::setUp();

        $this->documentBuilder = $this->getMock('Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface');
        $this->errorCompleter = $this->getMock('Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface');

        $this->processor = new BuildSingleItemResultDocument($this->documentBuilder, $this->errorCompleter);
    }

    public function testProcessContextWithoutErrorsOnEmptyResult()
    {
        $this->documentBuilder->expects($this->once())
            ->method('setDataObject')
            ->with(null);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');

        $this->context->setResult(null);
        $this->processor->process($this->context);
    }

    public function testProcessContextWithoutErrorsOnNonEmptyResult()
    {
        $result   = [new \stdClass()];
        $metadata = new EntityMetadata();

        $this->documentBuilder->expects($this->once())
            ->method('setDataObject')
            ->with($result, $metadata);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');

        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
    }

    public function testProcessWithErrors()
    {
        $error = new Error();

        $this->documentBuilder->expects($this->never())
            ->method('setDataObject');
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilder->expects($this->once())
            ->method('setErrorCollection')
            ->with([$error]);

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
            ->method('setDataObject')
            ->willThrowException($exception);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilder->expects($this->once())
            ->method('setErrorObject');

        $this->errorCompleter->expects($this->once())
            ->method('complete');

        $this->context->setResult(null);
        $this->processor->process($this->context);

        $this->assertEquals(500, $this->context->getResponseStatusCode());
    }
}
