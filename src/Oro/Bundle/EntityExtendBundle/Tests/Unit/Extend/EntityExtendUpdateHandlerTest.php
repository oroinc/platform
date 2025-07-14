<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Extend;

use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateHandler;
use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateProcessor;
use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateProcessorResult;
use Oro\Bundle\MaintenanceBundle\Maintenance\Mode as MaintenanceMode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityExtendUpdateHandlerTest extends TestCase
{
    private EntityExtendUpdateProcessor&MockObject $entityExtendUpdateProcessor;
    private MaintenanceMode&MockObject $maintenance;
    private EntityExtendUpdateHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityExtendUpdateProcessor = $this->createMock(EntityExtendUpdateProcessor::class);
        $this->maintenance = $this->createMock(MaintenanceMode::class);

        $this->handler = new EntityExtendUpdateHandler($this->entityExtendUpdateProcessor, $this->maintenance);
    }

    public function testUpdate(): void
    {
        $this->maintenance->expects(self::once())
            ->method('activate');

        $this->entityExtendUpdateProcessor->expects(self::once())
            ->method('processUpdate')
            ->willReturn(new EntityExtendUpdateProcessorResult(true));

        $result = $this->handler->update();
        self::assertTrue($result->isSuccessful());
        self::assertNull($result->getFailureMessage());
    }

    public function testUpdateWhenUpdateFailed(): void
    {
        $this->maintenance->expects(self::once())
            ->method('activate');

        $this->entityExtendUpdateProcessor->expects(self::once())
            ->method('processUpdate')
            ->willReturn(new EntityExtendUpdateProcessorResult(false));

        $result = $this->handler->update();
        self::assertFalse($result->isSuccessful());
        self::assertNull($result->getFailureMessage());
    }
}
