<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\UpdateFieldExclusions;

class UpdateFieldExclusionsTest extends ConfigProcessorTestCase
{
    /** @var UpdateFieldExclusions */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new UpdateFieldExclusions();
    }

    public function testProcessWhenNoExplicitlyConfiguredFieldNames()
    {
        $definition = new EntityDefinitionConfig();
        $definition->setExcludeAll();
        $definition->addField('field1');
        $definition->addField('field2')->setExcluded();
        $definition->addField('field3')->setExcluded(false);

        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertFalse($definition->getField('field1')->hasExcluded());
        self::assertTrue($definition->getField('field2')->hasExcluded());
        self::assertTrue($definition->getField('field2')->isExcluded());
        self::assertTrue($definition->getField('field3')->hasExcluded());
        self::assertFalse($definition->getField('field3')->isExcluded());
    }

    public function testProcess()
    {
        $definition = new EntityDefinitionConfig();
        $definition->setExcludeAll();
        $definition->addField('field1');
        $definition->addField('field2')->setExcluded();
        $definition->addField('field3')->setExcluded(false);
        $definition->addField('field4');
        $definition->addField('field5')->setExcluded();
        $definition->addField('field6')->setExcluded(false);

        $this->context->setExplicitlyConfiguredFieldNames(['field1', 'field2', 'field3']);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertFalse($definition->getField('field1')->hasExcluded());
        self::assertTrue($definition->getField('field2')->hasExcluded());
        self::assertTrue($definition->getField('field2')->isExcluded());
        self::assertTrue($definition->getField('field3')->hasExcluded());
        self::assertFalse($definition->getField('field3')->isExcluded());
        self::assertTrue($definition->getField('field4')->hasExcluded());
        self::assertTrue($definition->getField('field4')->isExcluded());
        self::assertTrue($definition->getField('field5')->hasExcluded());
        self::assertTrue($definition->getField('field5')->isExcluded());
        self::assertTrue($definition->getField('field6')->hasExcluded());
        self::assertFalse($definition->getField('field6')->isExcluded());
    }
}
