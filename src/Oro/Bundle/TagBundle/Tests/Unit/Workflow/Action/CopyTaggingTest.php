<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Workflow\Action;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Workflow\Action\CopyTagging;
use Oro\Bundle\TagBundle\Workflow\Action\CopyTaggingToNewEntity;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Tests\Unit\Action\Stub\StubStorage;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class CopyTaggingTest extends \PHPUnit\Framework\TestCase
{
    /** @var TagManager|\PHPUnit\Framework\MockObject\MockObject */
    private $tagManager;

    /** @var TaggableHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $taggableHelper;

    /** @var CopyTaggingToNewEntity */
    private $action;

    protected function setUp(): void
    {
        $this->tagManager = $this->createMock(TagManager::class);
        $this->taggableHelper = $this->createMock(TaggableHelper::class);

        $this->action = new CopyTagging(
            new ContextAccessor(),
            $this->tagManager,
            $this->taggableHelper
        );
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitialize()
    {
        $options = [
            'source' => $this->createMock(PropertyPathInterface::class),
            'destination' => $this->createMock(PropertyPathInterface::class),
            'organization' => $this->createMock(PropertyPathInterface::class),
        ];

        self::assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
        self::assertEquals(
            $options,
            ReflectionUtil::getPropertyValue($this->action, 'options')
        );
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $inputData, string $exception, string $exceptionMessage)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        $this->action->initialize($inputData);
    }

    public function initializeExceptionDataProvider(): array
    {
        return [
            [
                'inputData' => ['source' => 1],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Source must be valid property definition',
            ],
            [
                'inputData' => [
                    'source' => $this->createMock(PropertyPathInterface::class),
                    'destination' => 1,
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Destination must be valid property definition',
            ],
        ];
    }

    public function testExecuteMethod()
    {
        $source = $this->createMock(Taggable::class);
        $destination = $this->createMock(Taggable::class);
        $organization = $this->createMock(Organization::class);

        $data = new StubStorage([
            'source' => $source,
            'destination' => $destination,
            'organization' => $organization,
        ]);
        $options = [
            'source' => new PropertyPath('source'),
            'destination' => new PropertyPath('destination'),
            'organization' => new PropertyPath('organization'),
        ];

        $this->taggableHelper->expects(self::exactly(2))
            ->method('isTaggable')
            ->willReturn(true);

        $this->tagManager->expects(self::once())
            ->method('loadTagging')
            ->with($source, $organization);

        $tags = $this->createMock(Collection::class);

        $this->tagManager->expects(self::once())
            ->method('getTags')
            ->with($source)
            ->willReturn($tags);

        $this->tagManager->expects(self::once())
            ->method('setTags')
            ->with($destination, $tags);

        $this->tagManager->expects(self::once())
            ->method('saveTagging')
            ->with($destination, true, $organization);

        $this->action->initialize($options);
        $this->action->execute($data);
    }

    public function testExecuteMethodExceptionSource()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Object in path "source" in "copy_tagging" action should be taggable.'
        );

        $inputData = [
            'source' => $this->createMock(Taggable::class),
        ];

        $this->taggableHelper->expects(self::once())
            ->method('isTaggable')
            ->willReturn(false);

        $data = new StubStorage($inputData);
        $options = [
            'source' => new PropertyPath('source'),
            'destination' => new PropertyPath('destination'),
            'organization' => new PropertyPath('organization'),
        ];

        $this->action->initialize($options);
        $this->action->execute($data);
    }

    public function testExecuteMethodExceptionDestination()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Object in path "destination" in "copy_tagging" action should be taggable.'
        );

        $inputData = [
            'source' => $this->createMock(Taggable::class),
            'destination' => $this->createMock(Taggable::class),
        ];

        $this->taggableHelper->expects(self::exactly(2))
            ->method('isTaggable')
            ->willReturnOnConsecutiveCalls(true, false);

        $data = new StubStorage($inputData);
        $options = [
            'source' => new PropertyPath('source'),
            'destination' => new PropertyPath('destination'),
            'organization' => new PropertyPath('organization'),
        ];

        $this->action->initialize($options);
        $this->action->execute($data);
    }
}
