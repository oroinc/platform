<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Model\Action;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Model\Action\ExecuteJobAction;

use Oro\Component\Action\Model\ContextAccessor;

class ExecuteJobActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|JobExecutor
     */
    protected $jobExecutor;

    /**
     * @var ExecuteJobAction
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->jobExecutor = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Job\JobExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new ExecuteJobAction($this->contextAccessor, $this->jobExecutor);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown()
    {
        unset($this->contextAccessor, $this->jobExecutor, $this->action);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     * @param array $options
     * @param string $expectedExceptionMessage
     */
    public function testInitializeErrors(array $options, $expectedExceptionMessage)
    {
        $this->setExpectedException(
            'Oro\Component\Action\Exception\InvalidParameterException',
            $expectedExceptionMessage
        );
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
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
     * @param array $options
     * @param mixed $context
     * @param string $expectedExceptionMessage
     */
    public function testExecuteExceptions(array $options, $context, $expectedExceptionMessage)
    {
        $this->setExpectedException(
            'Oro\Component\Action\Exception\InvalidParameterException',
            $expectedExceptionMessage
        );
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @return array
     */
    public function invalidExecuteOptionsDataProvider()
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
        $jobResult = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Job\JobResult')
            ->disableOriginalConstructor()
            ->getMock();
        $this->jobExecutor->expects($this->once())
            ->method('executeJob')
            ->with('type', 'name', ['c' => 'test', 'd' => 'e'])
            ->will($this->returnValue($jobResult));
        $this->action->initialize($options);
        $this->action->execute($context);
        $expectedContext = new \stdClass();
        $expectedContext->a = 'name';
        $expectedContext->c = 'test';
        $expectedContext->attr = $jobResult;
        $this->assertEquals($expectedContext, $context);
    }
}
