<?php

namespace Oro\Bundle\UIBundle\Tests\Form\DataTransformer;

use Oro\Bundle\UIBundle\Form\DataTransformer\TreeItemToStringTransformer;
use Oro\Bundle\UIBundle\Model\TreeItem;

class TreeItemToStringTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TreeItemToStringTransformer */
    protected $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->transformer = new TreeItemToStringTransformer([
            'item1' => new TreeItem('item1', 'Item 1'),
            'item2' => new TreeItem('item2', 'Item 2'),
        ]);
    }

    /**
     * @dataProvider valueTransformDataProvider
     *
     * @param TreeItem|array $value
     * @param string|array   $result
     */
    public function testTransform($value, $result)
    {
        $this->assertEquals($result, $this->transformer->transform($value));
    }

    /**
     * @expectedException        \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Value must be instance of TreeItem or list of TreeItem[], but "stdClass" is given.
     */
    public function testTransformNotValidValue()
    {
        $this->transformer->transform(new \stdClass());
    }

    /**
     * @expectedException        \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Value must be instance of TreeItem or list of TreeItem[], but "stdClass" is given.
     */
    public function testTransformArrayHasNotValidValue()
    {
        $this->transformer->transform([new \stdClass()]);
    }

    /**
     * @dataProvider valueReverseTransformDataProvider
     *
     * @param TreeItem|array $value
     * @param string|array   $result
     */
    public function testReverseTransform($value, $result)
    {
        $this->assertEquals($result, $this->transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function valueTransformDataProvider()
    {
        return [
            'array' => [
                'value' => [new TreeItem('item1'), new TreeItem('item2')],
                'result' => ['item1', 'item2'],
            ],
            'single value' => [
                'value' => new TreeItem('item1'),
                'result' => 'item1',
            ]
        ];
    }

    /**
     * @return array
     */
    public function valueReverseTransformDataProvider()
    {
        return [
            'array' => [
                'value' => ['item1', 'item2'],
                'result' => [new TreeItem('item1', 'Item 1'), new TreeItem('item2', 'Item 2')],
            ],
            'single value' => [
                'value' => 'item1',
                'result' => new TreeItem('item1', 'Item 1'),
            ]
        ];
    }
}
