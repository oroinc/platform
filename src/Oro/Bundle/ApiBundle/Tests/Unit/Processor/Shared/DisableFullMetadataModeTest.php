<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\DisableFullMetadataMode;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class DisableFullMetadataModeTest extends GetProcessorTestCase
{
    private DisableFullMetadataMode $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new DisableFullMetadataMode();
    }

    public function testProcessWhenNoMetadata(): void
    {
        $this->context->setMetadata(null);
        $this->processor->process($this->context);
    }

    public function testProcessWhenMetadataExists(): void
    {
        $metadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::once())
            ->method('setEntityMetadataFullMode')
            ->with(false);

        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
    }
}
