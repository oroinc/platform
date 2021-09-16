<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Extend;

use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateHandler;
use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateProcessor;
use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateProcessorResult;

class EntityExtendUpdateHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityExtendUpdateProcessor|\PHPUnit\Framework\MockObject\MockObject */
    private $entityExtendUpdateProcessor;

    /** @var EntityExtendUpdateHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->entityExtendUpdateProcessor = $this->createMock(EntityExtendUpdateProcessor::class);

        $this->handler = new EntityExtendUpdateHandler($this->entityExtendUpdateProcessor);
    }

    public function testUpdate()
    {
        $this->entityExtendUpdateProcessor->expects(self::once())
            ->method('processUpdate')
            ->willReturn(new EntityExtendUpdateProcessorResult(true));

        $result = $this->handler->update();
        self::assertTrue($result->isSuccessful());
        self::assertNull($result->getFailureMessage());
    }

    public function testUpdateWhenUpdateFailed()
    {
        $this->entityExtendUpdateProcessor->expects(self::once())
            ->method('processUpdate')
            ->willReturn(new EntityExtendUpdateProcessorResult(false));

        $result = $this->handler->update();
        self::assertFalse($result->isSuccessful());
        self::assertNull($result->getFailureMessage());
    }
}
