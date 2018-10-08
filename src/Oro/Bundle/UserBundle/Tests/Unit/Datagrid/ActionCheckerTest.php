<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Datagrid\ActionChecker;

class ActionCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var ActionChecker */
    protected $actionChecker;

    public function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->actionChecker = new ActionChecker($this->tokenAccessor);
    }

    /**
     * @dataProvider actionCheckDataProvider
     */
    public function testCheckActions($current, $row, $result)
    {
        $resultRecord = $this->getMockBuilder(ResultRecordInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultRecord->expects($this->once())
            ->method('getValue')
            ->with('id')
            ->willReturn($row);

        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->willReturn($current);

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
