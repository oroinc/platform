<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ExportAction;

class ExportActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExportAction */
    protected $action;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->action = new ExportAction();
    }

    public function testGetOptions()
    {
        $this->assertEquals(
            ActionConfiguration::create([
                'frontend_type' => 'row/importexport',
                'type' => ExportAction::TYPE_EXPORT,
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
                'exportProcessor' => 'test_processor',
            ]))
        );
    }

    public function testSetOptionsWithoutExportProcessor()
    {
        $this->expectException(\Oro\Bundle\DataGridBundle\Exception\LogicException::class);
        $this->expectExceptionMessage('There is no option "exportProcessor" for action "test_name"');

        $this->assertSame(
            $this->action,
            $this->action->setOptions(ActionConfiguration::create([
                'name' => 'test_name',
            ]))
        );
    }
}
