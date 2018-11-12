<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\NoteBundle\Action\CreateNoteAction;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class CreateNoteActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var CreateNoteAction */
    private $action;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Note::class)
            ->willReturn($this->entityManager);

        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new CreateNoteAction($this->registry, $this->activityManager, $this->contextAccessor);
        $this->action->setDispatcher($this->eventDispatcher);
    }

    /**
     * @param array $options
     * @param string|PropertyPath $message
     * @param string|PropertyPath $target
     * @param null|string|PropertyPath $attribute
     *
     * @dataProvider initializeData
     */
    public function testAction(array $options, $message, $target, $attribute = null)
    {
        $this->action->initialize($options);

        $this->assertActionConfigured($message, $target, $attribute);
    }

    /**
     * @param string $message
     * @param string $target
     * @param null $attribute
     */
    private function assertActionConfigured($message, $target, $attribute = null)
    {
        $context = new ActionData([]);
        $targetObject = (object)['target_object'];

        $this->contextAccessor->expects($this->at(0))
            ->method('getValue')
            ->with($context, $message)
            ->willReturn('message_text');
        $this->contextAccessor->expects($this->at(1))
            ->method('getValue')
            ->with($context, $target)
            ->willReturn($targetObject);

        $checkNote = function ($note) {
            return $note instanceof Note && $note->getMessage() === 'message_text' && $note->isUpdatedAtSet();
        };

        $this->activityManager->expects($this->once())
            ->method('setActivityTargets')
            ->with($this->callback($checkNote), [$targetObject]);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Note::class));
        $this->entityManager->expects($this->once())->method('flush');

        $this->contextAccessor->expects($attribute ? $this->once() : $this->never())
            ->method('setValue')
            ->with($context, $attribute, $this->isInstanceOf(Note::class));

        $this->action->execute($context);
    }

    /**
     * @return \Generator
     */
    public function initializeData()
    {
        yield 'inline full' => [
            ['message', 'target', 'attribute'],
            'message',
            'target',
            'attribute'
        ];

        yield 'inline no attr' => [
            ['message', 'target'],
            'message',
            'target',
            null
        ];

        yield 'named full' => [
            [
                'message' => 'messagePath',
                'target_entity' => 'targetPath',
                'attribute' => 'attributePath'
            ],
            'messagePath',
            'targetPath',
            'attributePath'
        ];

        yield 'named no attr' => [
            [
                'message' => 'messagePath',
                'target_entity' => 'targetPath'
            ],
            'messagePath',
            'targetPath',
            null
        ];
    }

    /**
     * @param array $options
     * @param string $message
     *
     * @dataProvider initializeExceptions
     */
    public function testInitializeExceptions(array $options, $message)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage($message);

        $this->action->initialize($options);
    }

    /**
     * @return \Generator
     */
    public function initializeExceptions()
    {
        yield 'less than required' => [[], 'Two or three parameters are required.'];
        yield 'more than required' => [[1, 2, 3, 4], 'Two or three parameters are required.'];

        yield 'message is not set' => [
            ['message_wrong_key_with_typo' => 'message text', 'target_entity' => 'second arg'],
            'Parameter "message" has to be set.'
        ];

        yield 'target is not set' => [
            [
                'the message',
                'target_entety_typo' => 'asdasd'
            ],
            'Parameter "target_entity" has to be set.'
        ];
    }
}
