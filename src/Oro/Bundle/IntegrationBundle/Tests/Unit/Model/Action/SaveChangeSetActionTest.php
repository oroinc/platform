<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Action;

use Oro\Bundle\IntegrationBundle\Entity\ChangeSet;
use Oro\Bundle\IntegrationBundle\Manager\ChangeSetManager;
use Oro\Bundle\IntegrationBundle\Model\Action\SaveChangeSetAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Symfony\Component\PropertyAccess\PropertyPath;

class SaveChangeSetActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SaveChangeSetAction
     */
    protected $action;

    protected function setUp()
    {
        $contextAccessor = new ContextAccessor();
        $this->action    = new SaveChangeSetAction($contextAccessor);
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
            'empty'     => [
                [],
                'ChangeSet parameter is required'
            ],
            'changeSet' => [
                ['changeSet' => ['value']],
                'Data parameter is required'
            ],
            'data'      => [
                ['changeSet' => ['value'], 'data' => ['value']],
                'Type parameter is required'
            ],
            'full'      => [
                ['changeSet' => ['value'], 'data' => ['value'], 'type' => 'type'],
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

        if (!empty($context['changeSet'])) {
            $changeSetManager
                ->expects($this->once())
                ->method('setChanges')
                ->with(
                    $this->equalTo(
                        empty($context['data']) ? null : $context['data']
                    ),
                    $this->equalTo(
                        empty($context['type']) ? null : $context['type']
                    ),
                    $this->equalTo(
                        array_keys($context['changeSet'])
                    )
                );
        }

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
                    'data'      => new PropertyPath('data'),
                    'type'      => new PropertyPath('type'),
                    'changeSet' => new PropertyPath('changeSet'),
                ],
                [
                    'data'      => new \stdClass(),
                    'type'      => ChangeSet::TYPE_LOCAL,
                    'changeSet' => ['field' => ['old' => 1, 'new' => 2]],
                ]
            ],
            [
                [
                    'data'      => new PropertyPath('data'),
                    'type'      => new PropertyPath('type'),
                    'changeSet' => new PropertyPath('changeSet'),
                ],
                ['changeSet' => []]
            ]
        ];
    }
}
