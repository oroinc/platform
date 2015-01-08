<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CalendarBundle\Datagrid\SystemCalendarEventGridHelper;

class SystemCalendarEventGridHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var SystemCalendarEventGridHelper */
    protected $helper;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new SystemCalendarEventGridHelper($this->securityFacade);
    }

    /**
     * @dataProvider getPublicActionConfigurationClosureProvider
     */
    public function testGetPublicActionConfigurationClosure($isGranted, $expected)
    {
        $record = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_event_management')
            ->will($this->returnValue($isGranted));

        $closure = $this->helper->getPublicActionConfigurationClosure();
        $result  = call_user_func($closure, $record);
        $this->assertEquals($expected, $result);
    }

    public function getPublicActionConfigurationClosureProvider()
    {
        return [
            [
                false,
                [
                    'update' => false,
                    'delete' => false,
                ]
            ],
            [true, []]
        ];
    }

    /**
     * @dataProvider getSystemActionConfigurationClosureProvider
     */
    public function testGetSystemActionConfigurationClosure($isGranted, $expected)
    {
        $record = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_event_management')
            ->will($this->returnValue($isGranted));

        $closure = $this->helper->getSystemActionConfigurationClosure();
        $result  = call_user_func($closure, $record);
        $this->assertEquals($expected, $result);
    }

    public function getSystemActionConfigurationClosureProvider()
    {
        return [
            [
                false,
                [
                    'update' => false,
                    'delete' => false,
                ]
            ],
            [true, []]
        ];
    }
}
