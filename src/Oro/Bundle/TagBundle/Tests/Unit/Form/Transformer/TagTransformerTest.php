<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\Transformer;

use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Form\Transformer\TagTransformer;

class TagTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TagManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var TagTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(TagManager::class);

        $this->transformer = new TagTransformer($this->manager);
    }

    /**
     * @dataProvider valueReverseTransformProvider
     */
    public function testReverseTransform(string $value, array $tags)
    {
        $this->manager->expects($this->once())
            ->method('loadOrCreateTags')
            ->with($tags)
            ->willReturn([]);
        $this->transformer->reverseTransform($value);
    }

    public function valueReverseTransformProvider(): array
    {
        return [
            [
                'value' => '{"id":1,"name":"tag1"};;{"id":2,"name":"tag2"}',
                'tags'  => ['tag1', 'tag2']
            ]
        ];
    }

    /**
     * @dataProvider valueTransformProvider
     */
    public function testTransform(string $expected, array $value)
    {
        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    public function valueTransformProvider(): array
    {
        return [
            [
                'expected' => '{"id":1,"name":"tag1"};;{"id":2,"name":"tag2"}',
                'value'    => [['id' => 1, 'name' => 'tag1'], ['id' => 2, 'name' => 'tag2']],
            ]
        ];
    }
}
