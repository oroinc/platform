<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Datagrid\ActionChecker;

class ActionCheckerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /** @var ActionChecker */
    protected $actionChecker;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionChecker = new ActionChecker($this->securityFacade);
    }

    /**
     * @dataProvider actionCheckDataProvider
     */
    public function testCheckActions($curent, $row, $result)
    {
        $resultRecord = $this->getMockBuilder(ResultRecordInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultRecord->expects($this->once())
            ->method('getValue')
            ->with('id')
            ->willReturn($row);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUserId')
            ->willReturn($curent);

        $configuration = $this->actionChecker->checkActions($resultRecord);
        $this->assertSame($configuration, $result);
    }

    public function actionCheckDataProvider()
    {
        return [
            'delete available' => [
                'current' => 1,
                'row' => 2,
                'result' => [],
            ],
            'delete unavailable' => [
                'current' => 1,
                'row' => 1,
                'result' => ['delete' => false],
            ],
        ];
    }
}
