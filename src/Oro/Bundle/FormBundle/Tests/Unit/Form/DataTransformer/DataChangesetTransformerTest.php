<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Form\DataTransformer\DataChangesetTransformer;

class DataChangesetTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DataChangesetTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new DataChangesetTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     *
     * @param mixed $value
     * @param array $expected
     */
    public function testTransform($value, array $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    /**
     * @dataProvider transformDataProvider
     *
     * @param mixed $expected
     * @param array $value
     */
    public function testReverseTransform($expected, array $value)
    {
        if (!$expected) {
            $expected = new ArrayCollection();
        }

        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            [null,[]],
            [[],[]],
            [
                new ArrayCollection([
                    '1' => ['data' => ['test' => '123', 'test2' => 'val']],
                    '2' => ['data' => ['test' => '12']]
                ]),
                [
                    '1' => ['test' => '123', 'test2' => 'val'],
                    '2' => ['test' => '12']
                ]
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array", "string" given
     */
    public function testReverseTransformException()
    {
        $this->transformer->reverseTransform('test');
    }
}
