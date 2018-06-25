<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\Transformer;

use Oro\Bundle\TagBundle\Form\Transformer\TagTransformer;

class TagTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TagTransformer
     */
    protected $transformer;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $manager;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Oro\Bundle\TagBundle\Entity\TagManager')
            ->disableOriginalConstructor()->getMock();
        $this->transformer = new TagTransformer($this->manager);
    }

    protected function tearDown()
    {
        unset($this->manager);
        unset($this->transformer);
    }

    /**
     * @dataProvider valueReverseTransformProvider
     * @param $value
     * @param $tags
     */
    public function testReverseTransform($value, $tags)
    {
        $this->manager->expects($this->once())
            ->method('loadOrCreateTags')
            ->with($tags)
            ->willReturn([]);
        $this->transformer->reverseTransform($value);
    }

    /**
     * @return array
     */
    public function valueReverseTransformProvider()
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
     *
     * @param $expected
     * @param $value
     */
    public function testTransform($expected, $value)
    {
        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    /**
     * @return array
     */
    public function valueTransformProvider()
    {
        return [
            [
                'expected' => '{"id":1,"name":"tag1"};;{"id":2,"name":"tag2"}',
                'value'    => [['id' => 1, 'name' => 'tag1'], ['id' => 2, 'name' => 'tag2']],
            ]
        ];
    }
}
