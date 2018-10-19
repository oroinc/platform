<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Action;

use Oro\Bundle\IntegrationBundle\Manager\FieldsChangesManager;
use Oro\Bundle\IntegrationBundle\Model\Action\SaveFieldsChangesAction;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class SaveFieldsChangesActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SaveFieldsChangesAction
     */
    protected $action;

    protected function setUp()
    {
        $contextAccessor = new ContextAccessor();
        $this->action    = new SaveFieldsChangesAction($contextAccessor);
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
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
            $this->expectException('Oro\Component\Action\Exception\InvalidParameterException');
            $this->expectExceptionMessage($message);
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
                'changeSet parameter is required'
            ],
            'changeSet' => [
                ['changeSet' => ['value']],
                'Entity parameter is required'
            ],
            'full'      => [
                ['changeSet' => ['value'], 'entity' => ['value']],
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
        /** @var FieldsChangesManager|\PHPUnit\Framework\MockObject\MockObject $fieldsChangesManager */
        $fieldsChangesManager = $this
            ->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\FieldsChangesManager')
            ->disableOriginalConstructor()
            ->getMock();

        if (!empty($context['changeSet'])) {
            $fieldsChangesManager
                ->expects($this->once())
                ->method('setChanges')
                ->with(
                    $this->equalTo(
                        empty($context['data']) ? null : $context['data']
                    ),
                    $this->equalTo(
                        array_keys($context['changeSet'])
                    )
                );
        }

        $this->action->setFieldsChangesManager($fieldsChangesManager);
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
                    'entity'    => new PropertyPath('data'),
                    'changeSet' => new PropertyPath('changeSet'),
                ],
                [
                    'data'      => new \stdClass(),
                    'changeSet' => ['field' => ['old' => 1, 'new' => 2]],
                ]
            ],
            [
                [
                    'entity'    => new PropertyPath('entity'),
                    'changeSet' => new PropertyPath('changeSet'),
                ],
                ['changeSet' => []]
            ]
        ];
    }
}
