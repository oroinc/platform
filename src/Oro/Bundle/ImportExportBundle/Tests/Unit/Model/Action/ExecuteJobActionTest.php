<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Model\Action;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Model\Action\ExecuteJobAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class ExecuteJobActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $jobExecutor;

    /** @var ExecuteJobAction */
    private $action;

    protected function setUp(): void
    {
        $this->jobExecutor = $this->createMock(JobExecutor::class);

        $this->action = new ExecuteJobAction(new ContextAccessor(), $this->jobExecutor);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testInitializeErrors(array $options, string $expectedExceptionMessage)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->action->initialize($options);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            [[], 'Parameter "jobType" is required.'],
            [['jobType' => ''], 'Parameter "jobType" is required.'],
            [
                [
                    'jobType' => 'a',
                ],
                'Parameter "jobName" is required.'
            ],
            [
                [
                    'jobType' => 'a',
                    'jobName' => 'b'
                ],
                'Parameter "configuration" is required.'
            ],
            [
                [
                    'jobType' => 'a',
                    'jobName' => 'b',
                    'configuration' => []
                ],
                'Parameter "attribute" is required.'
            ],
        ];
    }

    public function testInitialize()
    {
        $options = [
            'jobType' => 'a',
            'jobName' => 'b',
            'configuration' => [],
            'attribute' => 'c'
        ];
        $this->assertSame($this->action, $this->action->initialize($options));
    }

    /**
     * @dataProvider invalidExecuteOptionsDataProvider
     */
    public function testExecuteExceptions(array $options, array $context, string $expectedExceptionMessage)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function invalidExecuteOptionsDataProvider(): array
    {
        return [
            'invalid jobType' => [
                [
                    'jobType' => new \DateTime('now'),
                    'jobName' => 'b',
                    'configuration' => [],
                    'attribute' => 'c'
                ],
                [],
                'Action "execute_job" expects string in parameter "jobType", DateTime is given.'
            ],
            'invalid jobName' => [
                [
                    'jobType' => 'type',
                    'jobName' => new \DateTime('now'),
                    'configuration' => [],
                    'attribute' => 'c'
                ],
                [],
                'Action "execute_job" expects string in parameter "jobName", DateTime is given.'
            ],
            'invalid configuration' => [
                [
                    'jobType' => 'type',
                    'jobName' => 'name',
                    'configuration' => 'config',
                    'attribute' => 'c'
                ],
                [],
                'Action "execute_job" expects array in parameter "configuration", string is given.'
            ],
            // Property path calls check
            'invalid jobType by PropertyPath' => [
                [
                    'jobType' => new PropertyPath('[a]'),
                    'jobName' => 'b',
                    'configuration' => [],
                    'attribute' => 'c'
                ],
                [
                    'a' => new \DateTime('now')
                ],
                'Action "execute_job" expects string in parameter "jobType", DateTime is given.'
            ],
            'invalid jobName by PropertyPath' => [
                [
                    'jobType' => 'type',
                    'jobName' => new PropertyPath('[a]'),
                    'configuration' => [],
                    'attribute' => 'c'
                ],
                [
                    'a' => new \DateTime('now')
                ],
                'Action "execute_job" expects string in parameter "jobName", DateTime is given.'
            ],
            'invalid configuration by PropertyPath' => [
                [
                    'jobType' => 'type',
                    'jobName' => 'name',
                    'configuration' => new PropertyPath('[a]'),
                    'attribute' => 'c'
                ],
                [
                    'a' => new \DateTime('now')
                ],
                'Action "execute_job" expects array in parameter "configuration", DateTime is given.'
            ]
        ];
    }

    public function testExecute()
    {
        $options = [
            'jobType' => 'type',
            'jobName' => new PropertyPath('a'),
            'configuration' => ['c' => new PropertyPath('c'), 'd' => 'e'],
            'attribute' => new PropertyPath('attr')
        ];
        $context = new \stdClass();
        $context->a = 'name';
        $context->c = 'test';
        $context->attr = null;
        $jobResult = $this->createMock(JobResult::class);
        $this->jobExecutor->expects($this->once())
            ->method('executeJob')
            ->with('type', 'name', ['c' => 'test', 'd' => 'e'])
            ->willReturn($jobResult);
        $this->action->initialize($options);
        $this->action->execute($context);
        $expectedContext = new \stdClass();
        $expectedContext->a = 'name';
        $expectedContext->c = 'test';
        $expectedContext->attr = $jobResult;
        $this->assertEquals($expectedContext, $context);
    }
}
