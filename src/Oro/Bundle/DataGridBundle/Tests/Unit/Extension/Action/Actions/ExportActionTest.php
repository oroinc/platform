<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ExportAction;

class ExportActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExportAction */
    protected $action;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->action = new ExportAction();
    }

    public function testGetOptions()
    {
        $this->assertEquals(
            ActionConfiguration::create([
                'frontend_type' => 'importexport',
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

    /**
     * @expectedException Oro\Bundle\DataGridBundle\Exception\LogicException
     * @expectedExceptionMessage There is no option "exportProcessor" for action "test_name"
     */
    public function testSetOptionsWithoutExportProcessor()
    {
        $this->assertSame(
            $this->action,
            $this->action->setOptions(ActionConfiguration::create([
                'name' => 'test_name',
            ]))
        );
    }
}
