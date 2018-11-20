<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\SetPrimaryEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class SetPrimaryEntityTest extends FormProcessorTestCase
{
    /** @var SetPrimaryEntity */
    private $processor;

    protected function setUp()
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
        self::assertNull($includedEntities->getPrimaryEntityMetadata());
    }

    public function testProcessWithPrimaryEntity()
    {
        $primaryEntity = new \stdClass();
        $primaryEntityMetadata = new EntityMetadata();
        $includedEntities = new IncludedEntityCollection();
        $includedEntities->setPrimaryEntityId('Test\Class', '123');

        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->setResult($primaryEntity);
        $this->context->setMetadata($primaryEntityMetadata);
        $this->processor->process($this->context);
        self::assertSame($primaryEntity, $includedEntities->getPrimaryEntity());
        self::assertSame($primaryEntityMetadata, $includedEntities->getPrimaryEntityMetadata());
    }
}
