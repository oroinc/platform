<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\RemoveEntityMapper;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\EntityMapper;

class RemoveEntityMapperTest extends FormProcessorTestCase
{
    /** @var RemoveEntityMapper */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new RemoveEntityMapper();
    }

    public function testProcess()
    {
        $this->context->setEntityMapper($this->createMock(EntityMapper::class));
        $this->processor->process($this->context);

        self::assertNull($this->context->getEntityMapper());
    }
}
