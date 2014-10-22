<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Action;

use Oro\Bundle\IntegrationBundle\Entity\ChangeSet;
use Oro\Bundle\IntegrationBundle\Manager\ChangeSetManager;
use Oro\Bundle\IntegrationBundle\Model\Action\RemoveChangeSetAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Symfony\Component\PropertyAccess\PropertyPath;

class RemoveChangeSetActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RemoveChangeSetAction
     */
    protected $action;

    protected function setUp()
    {
        $contextAccessor = new ContextAccessor();
        $this->action    = new RemoveChangeSetAction($contextAccessor);
    }

    /**
     * @param array  $options
     * @param string $message
     *
     * @dataProvider initializeDataProvider
     */
    public function testInitializeFailed(array $options, $message = null)
    {
        if ($message) {
            $this->setExpectedException(
                'Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                $message
            );
        }

        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeDataProvider()
    {
        return [
            'empty' => [
                [],
                'Data parameter is required'
            ],
            'data'  => [
                ['data' => ['value']],
                'Type parameter is required'
            ],
            'full'  => [
                ['data' => ['value'], 'type' => 'type'],
                null
            ],
        ];
    }

    /**
     * @param array $options
     * @param array $context
     *
     * @dataProvider executeDataProvider
     */
    public function testExecuteAction(array $options, array $context)
    {
        /** @var ChangeSetManager|\PHPUnit_Framework_MockObject_MockObject $changeSetManager */
        $changeSetManager = $this
            ->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\ChangeSetManager')
            ->disableOriginalConstructor()
            ->getMock();

        $changeSetManager
            ->expects($this->once())
            ->method('removeChanges')
            ->with(
                $this->equalTo(
                    empty($context['data']) ? null : $context['data']
                ),
                $this->equalTo(
                    empty($context['type']) ? null : $context['type']
                )
            );

        $this->action->setChangeSetManager($changeSetManager);
        $this->action->initialize($options);
        $this->action->execute(new ProcessData($context));
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                [
                    'data' => new PropertyPath('data'),
                    'type' => new PropertyPath('type'),
                ],
                [
                    'data' => new \stdClass(),
                    'type' => ChangeSet::TYPE_LOCAL
                ]
            ],
            [
                [
                    'data' => new PropertyPath('data'),
                    'type' => new PropertyPath('type'),
                ],
                []
            ]
        ];
    }
}
