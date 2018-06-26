<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Workflow\Action\CopyTaggingToNewEntity;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Tests\Unit\Action\Stub\StubStorage;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class CopyTaggingToNewEntityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TagManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tagManager;

    /**
     * @var TaggableHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $taggableHelper;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var CopyTaggingToNewEntity
     */
    private $action;

    protected function setUp()
    {
        $this->tagManager = $this->createMock(TagManager::class);
        $this->taggableHelper = $this->createMock(TaggableHelper::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->action = new CopyTaggingToNewEntity(
            new ContextAccessor(),
            $this->tagManager,
            $this->taggableHelper,
            $this->doctrineHelper
        );

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action->setDispatcher($dispatcher);
    }

    public function testInitialize()
    {
        $options = [
            'source' => $this->createMock(PropertyPathInterface::class),
            'destination' => $this->createMock(PropertyPathInterface::class),
            'organization' => $this->createMock(PropertyPathInterface::class),
        ];

        $this->assertInstanceOf(
            ActionInterface::class,
            $this->action->initialize($options)
        );

        static::assertAttributeEquals($options, 'options', $this->action);
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     *
     * @param array  $inputData
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testInitializeException(array $inputData, $exception, $exceptionMessage)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        $this->action->initialize($inputData);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
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

        $this->taggableHelper->expects(static::at(0))
            ->method('isTaggable')
            ->willReturn(true);

        $this->taggableHelper->expects(static::at(1))
            ->method('isTaggable')
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->method('getEntityManagerForClass')
            ->with(Tagging::class)
            ->willReturn($em);

        $this->tagManager->expects(static::once())
            ->method('loadTagging')
            ->with($source, $organization);

        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);

        $tags = new ArrayCollection([
            $tag1,
            $tag2,
        ]);

        $this->tagManager->expects(static::once())
            ->method('getTags')
            ->with($source)
            ->willReturn($tags);

        $this->tagManager->expects(static::once())
            ->method('setTags')
            ->with($destination, $tags);

        $em->expects(static::at(0))
            ->method('persist')
            ->will(static::returnCallback(function (Tagging $tagging) use ($tag1, $destination) {
                static::assertSame($tagging->getTag(), $tag1);
                static::assertEquals($tagging->getEntityName(), ClassUtils::getClass($destination));
            }));

        $em->expects(static::at(1))
            ->method('persist')
            ->will(static::returnCallback(function (Tagging $tagging) use ($tag2, $destination) {
                static::assertSame($tagging->getTag(), $tag2);
                static::assertEquals($tagging->getEntityName(), ClassUtils::getClass($destination));
            }));

        $em->expects(static::once())
            ->method('flush');

        $this->action->initialize($options);
        $this->action->execute($data);
    }

    public function testExecuteMethodExceptionSource()
    {
        $this->expectException(
            \LogicException::class
        );
        $this->expectExceptionMessage(
            'Object in path "source" in "copy_tagging_to_new_entity" action should be taggable.'
        );

        $inputData = [
            'source' => $this->createMock(Taggable::class),
        ];

        $this->taggableHelper->expects(static::once())
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
        $this->expectException(
            \LogicException::class
        );
        $this->expectExceptionMessage(
            'Object in path "destination" in "copy_tagging_to_new_entity" action should be taggable.'
        );

        $inputData = [
            'source' => $this->createMock(Taggable::class),
            'destination' => $this->createMock(Taggable::class),
        ];

        $this->taggableHelper->expects(static::at(0))
            ->method('isTaggable')
            ->willReturn(true);

        $this->taggableHelper->expects(static::at(1))
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
}
