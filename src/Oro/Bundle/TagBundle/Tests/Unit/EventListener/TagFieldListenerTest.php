<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\EventListener;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\EventListener\TagFieldListener;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Twig\Environment;

class TagFieldListenerTest extends \PHPUnit\Framework\TestCase
{
    private BeforeViewRenderEvent&MockObject $event;

    private TagFieldListener $tagFieldListener;

    private TaggableHelper&MockObject $taggableHelper;

    private Environment $environment;

    protected function setUp(): void
    {
        $this->event = $this->createMock(BeforeViewRenderEvent::class);
        $this->taggableHelper = $this->createMock(TaggableHelper::class);
        $this->environment = $this->createMock(Environment::class);

        $this->event->method('getTwigEnvironment')->willReturn($this->environment);

        $this->tagFieldListener = new TagFieldListener($this->taggableHelper);
    }

    public function testThatTagsFieldIsNotAddedWhenEntityIsNotSet()
    {
        $env = $this->createMock(Environment::class);

        $this->event->method('getTwigEnvironment')->willReturn($env);

        $env->expects($this->never())->method('render');

        $this->tagFieldListener->addTagField($this->event);
    }

    public function testThatTagsFieldIsNotAddedWhenVisibilityIsNotDefault()
    {
        $this->environment->expects($this->never())->method('render');

        $this->event->method('getEntity')->willReturn($this->createMock(Tag::class));

        $this->taggableHelper->method('shouldRenderDefault')->willReturn(false);

        $this->tagFieldListener->addTagField($this->event);
    }

    public function testThatTagsFieldIsRendered()
    {
        $this->environment->expects($this->once())->method('render');

        $this->event->method('getEntity')->willReturn($this->createMock(Tag::class));

        $this->taggableHelper->method('shouldRenderDefault')->willReturn(true);

        $this->tagFieldListener->addTagField($this->event);
    }

    /**
     * @dataProvider dataBlocksProvider
     * @return void
     */
    public function testThatRenderedTagsFieldIsNotAdded(array $dataBlocks)
    {
        $this->event->method('getEntity')->willReturn($this->createMock(Tag::class));

        $this->taggableHelper->method('shouldRenderDefault')->willReturn(true);

        $this->event->method('getData')->willReturn([
            'dataBlocks' => $dataBlocks
        ]);

        $previousStateOfData = $this->event->getData();

        $this->tagFieldListener->addTagField($this->event);

        self::assertSame($previousStateOfData, $this->event->getData());
    }

    /**
     * @dataProvider dataBlocksKeyProvider
     * @return void
     */
    public function testThatRenderedTagsFieldIsAddedToData(string|int $arrayKey)
    {
        $this->environment->expects($this->once())->method('render')
            ->willReturn('tags block');

        $this->event->method('getEntity')->willReturn($this->createMock(Tag::class));

        $this->event->method('getData')->willReturn([
            'dataBlocks' => [
                $arrayKey => [
                    'subblocks' => [
                        0 => [
                            'data' => []
                        ]
                    ]
                ]
            ]
        ]);

        $this->taggableHelper->method('shouldRenderDefault')->willReturn(true);

        $this->event->expects($this->once())->method('setData')->with(
            [
                'dataBlocks' => [
                    $arrayKey => [
                        'subblocks' => [
                            0 => [
                                'data' => [
                                    'tags block'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->tagFieldListener->addTagField($this->event);
    }

    private function dataBlocksProvider(): array
    {
        return [
            'When dataBlocks is empty' => [
                []
            ],
            'When dataSubBlocks not exists' => [
                [
                    0 => [
                        'notsubblocks' => []
                    ]
                ]
            ]
        ];
    }

    private function dataBlocksKeyProvider(): array
    {
        return [
            [0],
            ['custom_key']
        ];
    }
}
