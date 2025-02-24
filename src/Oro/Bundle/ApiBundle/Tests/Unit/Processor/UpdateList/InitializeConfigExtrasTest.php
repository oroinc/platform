<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Processor\UpdateList\InitializeConfigExtras;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;

class InitializeConfigExtrasTest extends UpdateListProcessorTestCase
{
    /** @var InitializeConfigExtras */
    private $processor;

    #[\Override]
    protected function setUp(): void
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
                new EntityDefinitionConfigExtra($this->context->getAction(), true)
            ],
            $this->context->getConfigExtras()
        );
    }
}
