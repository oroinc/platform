<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Create\InitializeConfigExtras;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;

class InitializeConfigExtrasTest extends FormProcessorTestCase
{
    /** @var InitializeConfigExtras */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new InitializeConfigExtras();
    }

    public function testProcess()
    {
        $this->context->setConfigExtras([]);

        $existingExtra = new TestConfigExtra('test');
        $this->context->addConfigExtra($existingExtra);

        $this->context->setAction('test_action');
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                new TestConfigExtra('test'),
                new EntityDefinitionConfigExtra($this->context->getAction())
            ],
            $this->context->getConfigExtras()
        );
    }
}
