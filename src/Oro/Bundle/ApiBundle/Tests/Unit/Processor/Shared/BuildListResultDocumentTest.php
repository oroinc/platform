<?php


namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildListResultDocument;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class BuildListResultDocumentTest extends GetListProcessorTestCase
{
    /** @var BuildListResultDocument */
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

        $this->processor = new BuildListResultDocument($this->documentBuilder, $this->errorCompleter);
    }

    public function testProcessContextWithoutErrorsOnEmptyResult()
    {
        $this->documentBuilder->expects($this->once())
            ->method('setDataCollection')
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
            ->method('setDataCollection')
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
            ->method('setDataCollection');
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
            ->method('setDataCollection')
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
