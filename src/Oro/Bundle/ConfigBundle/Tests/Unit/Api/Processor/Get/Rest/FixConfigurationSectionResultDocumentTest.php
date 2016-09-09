<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Processor\Get\Rest;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ConfigBundle\Api\Processor\Get\Rest\FixConfigurationSectionResultDocument;

class FixConfigurationSectionResultDocumentTest extends GetProcessorTestCase
{
    /** @var FixConfigurationSectionResultDocument */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new FixConfigurationSectionResultDocument();
    }

    public function testProcessWithEmptyResult()
    {
        $this->context->setResult([]);
        $this->context->setResponseStatusCode(200);
        $this->processor->process($this->context);

        $this->assertEquals(
            [],
            $this->context->getResult()
        );
    }

    public function testProcessWithFailResponse()
    {
        $data = ['options' => [['scope' => 'test', 'key' => 'key1', 'dataType' => 'string']]];

        $this->context->setResult($data);
        $this->context->setResponseStatusCode(400);
        $this->processor->process($this->context);

        $this->assertEquals(
            $data,
            $this->context->getResult()
        );
    }

    public function testProcessWithSuccessResponse()
    {
        $data = ['options' => [['scope' => 'test', 'key' => 'key1', 'dataType' => 'string']]];
        $expectedData = [['key' => 'key1', 'type' => 'string']];

        $this->context->setResult($data);
        $this->context->setResponseStatusCode(200);
        $this->processor->process($this->context);

        $this->assertEquals(
            $expectedData,
            $this->context->getResult()
        );
    }
}
