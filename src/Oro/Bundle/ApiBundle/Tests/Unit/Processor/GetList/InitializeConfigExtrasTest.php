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

    public function testProcessWhenConfigExtrasAreAlreadyInitialized()
    {
        $this->context->setConfigExtras([]);
        $this->context->addConfigExtra(new EntityDefinitionConfigExtra());

        $this->context->setAction('test_action');
        $this->processor->process($this->context);

        $this->assertEquals(
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

        $this->assertEquals(
            [
                new TestConfigExtra('test'),
                new EntityDefinitionConfigExtra($this->context->getAction()),
                new CustomizeLoadedDataConfigExtra(),
                new DataTransformersConfigExtra(),
                new FiltersConfigExtra(),
                new SortersConfigExtra()
            ],
            $this->context->getConfigExtras()
        );
    }
}
