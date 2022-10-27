<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Action;

use Oro\Bundle\IntegrationBundle\Manager\FieldsChangesManager;
use Oro\Bundle\IntegrationBundle\Model\Action\RemoveFieldsChangesAction;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class RemoveFieldsChangesActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var RemoveFieldsChangesAction */
    private $action;

    protected function setUp(): void
    {
        $this->action = new RemoveFieldsChangesAction(new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider initializeDataProvider
     */
    public function testInitializeFailed(array $options, ?string $message)
    {
        if ($message) {
            $this->expectException(InvalidParameterException::class);
            $this->expectExceptionMessage($message);
        }

        $this->action->initialize($options);
    }

    public function initializeDataProvider(): array
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
     * @dataProvider executeDataProvider
     */
    public function testExecuteAction(array $options, array $context)
    {
        $fieldsChangesManager = $this->createMock(FieldsChangesManager::class);

        if (!empty($context['entity'])) {
            $fieldsChangesManager->expects($this->once())
                ->method('removeChanges')
                ->with($this->equalTo($context['entity']));
        }

        $this->action->setFieldsChangesManager($fieldsChangesManager);
        $this->action->initialize($options);
        $this->action->execute(new ProcessData($context));
    }

    public function executeDataProvider(): array
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
