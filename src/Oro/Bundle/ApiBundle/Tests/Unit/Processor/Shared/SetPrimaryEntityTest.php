<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Processor\Shared\SetPrimaryEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class SetPrimaryEntityTest extends FormProcessorTestCase
{
    /** @var SetPrimaryEntity */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->processor = new SetPrimaryEntity();
    }

    public function testProcessWithoutIncludedData()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWithoutIncludedEntities()
    {
        $this->context->setIncludedData(['key' => 'value']);
        $this->processor->process($this->context);
    }

    public function testProcessWithoutPrimaryEntity()
    {
        $includedEntities = new IncludedEntityCollection();

        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);
        self::assertNull($includedEntities->getPrimaryEntity());
    }

    public function testProcessWithPrimaryEntity()
    {
        $primaryEntity = new \stdClass();
        $includedEntities = new IncludedEntityCollection();
        $includedEntities->setPrimaryEntityId('Test\Class', '123');

        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->setResult($primaryEntity);
        $this->processor->process($this->context);
        self::assertSame($primaryEntity, $includedEntities->getPrimaryEntity());
    }
}
