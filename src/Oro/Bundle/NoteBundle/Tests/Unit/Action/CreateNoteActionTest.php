<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Action;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\NoteBundle\Action\CreateNoteAction;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CreateNoteActionTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ActivityManager&MockObject $activityManager;
    private ContextAccessor&MockObject $contextAccessor;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private CreateNoteAction $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Note::class)
            ->willReturn($this->entityManager);

        $this->action = new CreateNoteAction($doctrine, $this->activityManager, $this->contextAccessor);
        $this->action->setDispatcher($this->eventDispatcher);
    }

    /**
     * @dataProvider initializeData
     */
    public function testAction(array $options, string $message, string $target, ?string $attribute = null): void
    {
        $this->action->initialize($options);

        $this->assertActionConfigured($message, $target, $attribute);
    }

    private function assertActionConfigured(string $message, string $target, ?string $attribute = null): void
    {
        $context = new ActionData([]);
        $targetObject = (object)['target_object'];

        $this->contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                [$context, $message, 'message_text'],
                [$context, $target, $targetObject]
            ]);

        $checkNote = function ($note) {
            return $note instanceof Note && $note->getMessage() === 'message_text' && $note->isUpdatedAtSet();
        };

        $this->activityManager->expects($this->once())
            ->method('setActivityTargets')
            ->with($this->callback($checkNote), [$targetObject]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Note::class));
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->contextAccessor->expects($attribute ? $this->once() : $this->never())
            ->method('setValue')
            ->with($context, $attribute, $this->isInstanceOf(Note::class));

        $this->action->execute($context);
    }

    public function initializeData(): array
    {
        return [
            'inline full'    => [
                ['message', 'target', 'attribute'],
                'message',
                'target',
                'attribute'
            ],
            'inline no attr' => [
                ['message', 'target'],
                'message',
                'target',
                null
            ],
            'named full'     => [
                [
                    'message'       => 'messagePath',
                    'target_entity' => 'targetPath',
                    'attribute'     => 'attributePath'
                ],
                'messagePath',
                'targetPath',
                'attributePath'
            ],
            'named no attr'  => [
                [
                    'message'       => 'messagePath',
                    'target_entity' => 'targetPath'
                ],
                'messagePath',
                'targetPath',
                null
            ]
        ];
    }

    /**
     * @dataProvider initializeExceptions
     */
    public function testInitializeExceptions(array $options, string $message): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage($message);

        $this->action->initialize($options);
    }

    public function initializeExceptions(): array
    {
        return [
            'less than required' => [[], 'Two or three parameters are required.'],
            'more than required' => [[1, 2, 3, 4], 'Two or three parameters are required.'],
            'message is not set' => [
                ['message_wrong_key_with_typo' => 'message text', 'target_entity' => 'second arg'],
                'Parameter "message" has to be set.'
            ],
            'target is not set'  => [
                [
                    'the message',
                    'target_entety_typo' => 'asdasd'
                ],
                'Parameter "target_entity" has to be set.'
            ]
        ];
    }
}
