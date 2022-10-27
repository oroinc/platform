<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ImportAction;

class ImportActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImportAction */
    private $action;

    protected function setUp(): void
    {
        $this->action = new ImportAction();
    }

    public function testGetOptions()
    {
        $this->assertEquals(
            ActionConfiguration::create([
                'frontend_type' => 'row/importexport',
                'type' => ImportAction::TYPE_IMPORT,
            ]),
            $this->action->getOptions()
        );
    }

    public function testSetOptions()
    {
        $this->assertSame(
            $this->action,
            $this->action->setOptions(ActionConfiguration::create([
                'name' => 'test_name',
                'importProcessor' => 'test_processor',
            ]))
        );
    }

    public function testSetOptionsWithoutExportProcessor()
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
