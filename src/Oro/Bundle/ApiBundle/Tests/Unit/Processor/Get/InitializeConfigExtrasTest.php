<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get;

use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\VirtualFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Get\InitializeConfigExtras;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;

class InitializeConfigExtrasTest extends GetProcessorTestCase
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
        $existingExtra = new TestConfigExtra('test');
        $this->context->setConfigExtras([]);
        $this->context->addConfigExtra($existingExtra);

        $this->processor->process($this->context);

        $this->assertCount(3, $this->context->getConfigExtras());
        $this->assertTrue($this->context->hasConfigExtra($existingExtra->getName()));
        $this->assertTrue($this->context->hasConfigExtra(VirtualFieldsConfigExtra::NAME));
        $this->assertTrue($this->context->hasConfigExtra(FiltersConfigExtra::NAME));
    }
}
