<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ExportAction;
use PHPUnit\Framework\TestCase;

class ExportActionTest extends TestCase
{
    private ExportAction $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = new ExportAction();
    }

    public function testGetOptions(): void
    {
        $this->assertEquals(
            ActionConfiguration::create([
                'frontend_type' => 'row/importexport',
                'type' => ExportAction::TYPE_EXPORT,
            ]),
            $this->action->getOptions()
        );
    }

    public function testSetOptions(): void
    {
        $this->assertSame(
            $this->action,
            $this->action->setOptions(ActionConfiguration::create([
                'name' => 'test_name',
                'exportProcessor' => 'test_processor',
            ]))
        );
    }

    public function testSetOptionsWithoutExportProcessor(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is no option "exportProcessor" for action "test_name"');

        $this->assertSame(
            $this->action,
            $this->action->setOptions(ActionConfiguration::create([
                'name' => 'test_name',
            ]))
        );
    }
}
