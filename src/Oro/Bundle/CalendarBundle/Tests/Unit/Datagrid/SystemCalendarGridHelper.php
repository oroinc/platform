<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CalendarBundle\Datagrid\SystemCalendarGridHelper;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

class SystemCalendarGridHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityProvider;

    /** @var SystemCalendarGridHelper */
    protected $helper;

    protected function setUp()
    {
        $this->entityProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new SystemCalendarGridHelper($this->entityProvider);
    }

    public function testGetActionConfigurationClosure()
    {
        $resultRecord = new ResultRecord(array('public' => true));

        $closure = $this->helper->getActionConfigurationClosure();
        $this->assertEquals([
            'update' => false,
            'delete' => false,
        ],
        $closure($resultRecord));
    }
}
