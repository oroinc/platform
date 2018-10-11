<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Options\InitializeSubresourceConfigExtras;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;

class InitializeSubresourceConfigExtrasTest extends OptionsProcessorTestCase
{
    /** @var InitializeSubresourceConfigExtras */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new InitializeSubresourceConfigExtras();
    }

    public function testProcessWhenConfigExtrasAreAlreadyInitialized()
    {
        $this->context->setConfigExtras([]);
        $this->context->addConfigExtra(new EntityDefinitionConfigExtra());

        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('test');
        $this->context->setIsCollection(true);
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

        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('test');
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                new TestConfigExtra('test'),
                new EntityDefinitionConfigExtra(
                    $this->context->getAction(),
                    $this->context->isCollection(),
                    $this->context->getParentClassName(),
                    $this->context->getAssociationName()
                ),
                new FilterIdentifierFieldsConfigExtra()
            ],
            $this->context->getConfigExtras()
        );
    }
}
