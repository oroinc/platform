<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Datagrid\ActionChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionCheckerTest extends TestCase
{
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private ActionChecker $actionChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->actionChecker = new ActionChecker($this->tokenAccessor);
    }

    /**
     * @dataProvider actionCheckDataProvider
     */
    public function testCheckActions($current, $row, $result): void
    {
        $resultRecord = $this->createMock(ResultRecordInterface::class);

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

    public function actionCheckDataProvider(): array
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
