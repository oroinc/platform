<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class CompleteDefinitionTest extends ConfigProcessorTestCase
{
    /** @var CompleteDefinition */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new CompleteDefinition();
    }

    public function testProcess()
    {
        $config = [];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getResult()
        );
    }
}
