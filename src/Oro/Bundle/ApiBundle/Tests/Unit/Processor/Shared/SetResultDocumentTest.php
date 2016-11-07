<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\SetResultDocument;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class SetResultDocumentTest extends GetProcessorTestCase
{
    /** @var SetResultDocument */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SetResultDocument();
    }

    public function testProcessWhenResultExistsButNoResponseDocumentBuilder()
    {
        $result = [];
        $this->context->setResult($result);
        $this->processor->process($this->context);
        self::assertSame($result, $this->context->getResult());
    }

    public function testProcessWhenResponseDocumentBuilderExists()
    {
        $resultDocument = [];
        $documentBuilder = $this->getMock(DocumentBuilderInterface::class);
        $documentBuilder->expects($this->once())
            ->method('getDocument')
            ->willReturn($resultDocument);

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->processor->process($this->context);
        self::assertSame($resultDocument, $this->context->getResult());
        $this->assertNull($this->context->getResponseDocumentBuilder());
    }

    public function testProcessWhenBothResultAndResponseDocumentBuilderExists()
    {
        $result = ['result'];
        $resultDocument = ['resultDocument'];
        $documentBuilder = $this->getMock(DocumentBuilderInterface::class);
        $documentBuilder->expects($this->once())
            ->method('getDocument')
            ->willReturn($resultDocument);

        $this->context->setResult($result);
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->processor->process($this->context);
        self::assertSame($resultDocument, $this->context->getResult());
        $this->assertNull($this->context->getResponseDocumentBuilder());
    }
}
