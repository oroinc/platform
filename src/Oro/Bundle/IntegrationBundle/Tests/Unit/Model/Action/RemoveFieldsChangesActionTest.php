<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\IntegrationBundle\Manager\FieldsChangesManager;
use Oro\Bundle\IntegrationBundle\Model\Action\RemoveFieldsChangesAction;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

use Oro\Component\Action\Model\ContextAccessor;

class RemoveFieldsChangesActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RemoveFieldsChangesAction
     */
    protected $action;

    protected function setUp()
    {
        $contextAccessor = new ContextAccessor();
        $this->action    = new RemoveFieldsChangesAction($contextAccessor);
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
            $this->setExpectedException(
                'Oro\Component\Action\Exception\InvalidParameterException',
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
                'Entity parameter is required'
            ],
            'full'  => [
                ['entity' => ['value']],
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
        /** @var FieldsChangesManager|\PHPUnit_Framework_MockObject_MockObject $fieldsChangesManager */
        $fieldsChangesManager = $this
            ->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\FieldsChangesManager')
            ->disableOriginalConstructor()
            ->getMock();

        if (!empty($context['entity'])) {
            $fieldsChangesManager
                ->expects($this->once())
                ->method('removeChanges')
                ->with($this->equalTo($context['entity']));
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
                ['entity' => new PropertyPath('entity')],
                ['data' => new \stdClass()]
            ],
            [
                ['entity' => new PropertyPath('entity')],
                []
            ]
        ];
    }
}
