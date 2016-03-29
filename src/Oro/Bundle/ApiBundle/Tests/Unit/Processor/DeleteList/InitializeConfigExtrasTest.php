<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\DeleteList\InitializeConfigExtras;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;

class InitializeConfigExtrasTest extends DeleteListProcessorTestCase
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

        $this->processor->process($this->context);

        $this->assertCount(2, $this->context->getConfigExtras());
        $this->assertTrue($this->context->hasConfigExtra(EntityDefinitionConfigExtra::NAME));
        $this->assertTrue($this->context->hasConfigExtra(FiltersConfigExtra::NAME));
    }

    public function testProcessWithExtra()
    {
        $existingExtra = new TestConfigExtra('test');

        $this->context->setConfigExtras([]);
        $this->context->addConfigExtra($existingExtra);

        $this->processor->process($this->context);

        $this->assertCount(3, $this->context->getConfigExtras());
        $this->assertTrue($this->context->hasConfigExtra($existingExtra->getName()));
        $this->assertTrue($this->context->hasConfigExtra(EntityDefinitionConfigExtra::NAME));
        $this->assertTrue($this->context->hasConfigExtra(FiltersConfigExtra::NAME));
    }
}
