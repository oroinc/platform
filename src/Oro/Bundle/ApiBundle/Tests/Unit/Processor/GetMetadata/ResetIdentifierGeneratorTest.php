<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\ResetIdentifierGenerator;

class ResetIdentifierGeneratorTest extends MetadataProcessorTestCase
{
    private ResetIdentifierGenerator $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ResetIdentifierGenerator();
    }

    public function testProcessWhenNoMetadata(): void
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasResult());
    }

    public function testProcess(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setHasIdentifierGenerator(true);

        $this->context->setResult($metadata);
        $this->processor->process($this->context);
        self::assertFalse($metadata->hasIdentifierGenerator());
    }
}
