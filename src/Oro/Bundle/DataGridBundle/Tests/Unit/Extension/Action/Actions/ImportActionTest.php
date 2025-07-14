<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ImportAction;
use PHPUnit\Framework\TestCase;

class ImportActionTest extends TestCase
{
    private ImportAction $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = new ImportAction();
    }

    public function testGetOptions(): void
    {
        $this->assertEquals(
            ActionConfiguration::create([
                'frontend_type' => 'row/importexport',
                'type' => ImportAction::TYPE_IMPORT,
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
                'importProcessor' => 'test_processor',
            ]))
        );
    }

    public function testSetOptionsWithoutExportProcessor(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is no option "importProcessor" for action "test_name"');

        $this->assertSame(
            $this->action,
            $this->action->setOptions(ActionConfiguration::create([
                'name' => 'test_name',
            ]))
        );
    }
}
