<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Config\CustomizeLoadedDataConfigExtra;
use Oro\Bundle\ApiBundle\Config\DataTransformersConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetList\InitializeConfigExtras;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;

class InitializeConfigExtrasTest extends GetListProcessorTestCase
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

        $this->assertCount(6, $this->context->getConfigExtras());
        $this->assertTrue($this->context->hasConfigExtra($existingExtra->getName()));
        $this->assertTrue($this->context->hasConfigExtra(EntityDefinitionConfigExtra::NAME));
        $this->assertTrue($this->context->hasConfigExtra(CustomizeLoadedDataConfigExtra::NAME));
        $this->assertTrue($this->context->hasConfigExtra(DataTransformersConfigExtra::NAME));
        $this->assertTrue($this->context->hasConfigExtra(FiltersConfigExtra::NAME));
        $this->assertTrue($this->context->hasConfigExtra(SortersConfigExtra::NAME));
    }
}
