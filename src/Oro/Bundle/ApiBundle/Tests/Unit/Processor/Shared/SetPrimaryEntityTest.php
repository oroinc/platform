<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\SetPrimaryEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class SetPrimaryEntityTest extends FormProcessorTestCase
{
    private SetPrimaryEntity $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetPrimaryEntity();
    }

    public function testProcessWithoutIncludedData(): void
    {
        $this->processor->process($this->context);
    }

    public function testProcessWithoutIncludedEntities(): void
    {
        $this->context->setIncludedData(['key' => 'value']);
        $this->processor->process($this->context);
    }

    public function testProcessWithoutPrimaryEntity(): void
    {
        $includedEntities = new IncludedEntityCollection();

        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);
        self::assertNull($includedEntities->getPrimaryEntity());
        self::assertNull($includedEntities->getPrimaryEntityMetadata());
    }

    public function testProcessWithPrimaryEntity(): void
    {
        $primaryEntity = new \stdClass();
        $primaryEntityMetadata = new EntityMetadata('Test\Entity');
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
