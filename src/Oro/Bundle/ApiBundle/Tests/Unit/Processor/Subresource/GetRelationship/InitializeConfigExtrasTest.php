<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetRelationship;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetRelationship\InitializeConfigExtras;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;

class InitializeConfigExtrasTest extends GetSubresourceProcessorTestCase
{
    /** @var InitializeConfigExtras */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new InitializeConfigExtras();
    }

    public function testProcessForToOneAssociation()
    {
        $existingExtra = new TestConfigExtra('test');
        $this->context->addConfigExtra($existingExtra);

        $this->context->setAction('test_action');
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                new TestConfigExtra('test'),
                new EntityDefinitionConfigExtra($this->context->getAction()),
                new FilterIdentifierFieldsConfigExtra()
            ],
            $this->context->getConfigExtras()
        );
    }

    public function testProcessForToManyAssociation()
    {
        $existingExtra = new TestConfigExtra('test');
        $this->context->addConfigExtra($existingExtra);

        $this->context->setIsCollection(true);
        $this->context->setAction('test_action');
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                new TestConfigExtra('test'),
                new EntityDefinitionConfigExtra($this->context->getAction()),
                new FilterIdentifierFieldsConfigExtra(),
                new FiltersConfigExtra(),
                new SortersConfigExtra()
            ],
            $this->context->getConfigExtras()
        );
    }
}
