<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Processor\GetList\Rest;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ConfigBundle\Api\Processor\GetList\Rest\FixConfigurationSectionsResultDocument;

class FixConfigurationSectionsResultDocumentTest extends GetListProcessorTestCase
{
    /** @var FixConfigurationSectionsResultDocument */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new FixConfigurationSectionsResultDocument();
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
        $data = [['id' => 'section1', 'options' => [['scope' => 'test', 'key' => 'key1', 'dataType' => 'string']]]];

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
        $data = [['id' => 'section1', 'options' => [['scope' => 'test', 'key' => 'key1', 'dataType' => 'string']]]];
        $expectedData = ['section1'];

        $this->context->setResult($data);
        $this->context->setResponseStatusCode(200);
        $this->processor->process($this->context);

        $this->assertEquals(
            $expectedData,
            $this->context->getResult()
        );
    }
}
