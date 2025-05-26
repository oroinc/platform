<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\RemoveEntityMapper;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\EntityMapper;

class RemoveEntityMapperTest extends FormProcessorTestCase
{
    private RemoveEntityMapper $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new RemoveEntityMapper();
    }

    public function testProcess(): void
    {
        $this->context->setEntityMapper($this->createMock(EntityMapper::class));
        $this->processor->process($this->context);

        self::assertNull($this->context->getEntityMapper());
    }
}
