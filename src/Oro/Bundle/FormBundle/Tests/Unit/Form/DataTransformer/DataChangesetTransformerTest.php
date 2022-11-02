<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Form\DataTransformer\DataChangesetTransformer;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class DataChangesetTransformerTest extends \PHPUnit\Framework\TestCase
{
    private DataChangesetTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new DataChangesetTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(mixed $value, array $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testReverseTransform(mixed $expected, array $value)
    {
        if (!$expected) {
            $expected = new ArrayCollection();
        }

        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function transformDataProvider(): array
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

    public function testReverseTransformException()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "string" given');

        $this->transformer->reverseTransform('test');
    }
}
