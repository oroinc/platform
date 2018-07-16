<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Delete\InitializeConfigExtras;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;

class InitializeConfigExtrasTest extends DeleteProcessorTestCase
{
    /** @var InitializeConfigExtras */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new InitializeConfigExtras();
    }

    public function testProcessWhenConfigExtrasAreAlreadyInitialized()
    {
        $this->context->setConfigExtras([]);
        $this->context->addConfigExtra(new EntityDefinitionConfigExtra());

        $this->context->setAction('test_action');
        $this->processor->process($this->context);

        self::assertEquals(
            [new EntityDefinitionConfigExtra()],
            $this->context->getConfigExtras()
        );
    }

    public function testProcess()
    {
        $this->context->setConfigExtras([]);

        $existingExtra = new TestConfigExtra('test');
        $this->context->addConfigExtra($existingExtra);

        $this->context->setAction('test_action');
        $this->processor->process($this->context);

        self::assertEquals(
            [
                new TestConfigExtra('test'),
                new EntityDefinitionConfigExtra($this->context->getAction()),
                new FilterIdentifierFieldsConfigExtra()
            ],
            $this->context->getConfigExtras()
        );
    }
}
