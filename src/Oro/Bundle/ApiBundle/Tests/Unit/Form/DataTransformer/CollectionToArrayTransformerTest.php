<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Form\DataTransformer\CollectionToArrayTransformer;

class CollectionToArrayTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $elementTransformer;

    /** @var CollectionToArrayTransformer */
    protected $transformer;

    protected function setUp()
    {
        $this->elementTransformer = $this->createMock('Symfony\Component\Form\DataTransformerInterface');

        $this->transformer = new CollectionToArrayTransformer($this->elementTransformer);
    }

    public function testTransform()
    {
        $this->assertNull($this->transformer->transform(new ArrayCollection()));
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($value, $expected)
    {
        $this->elementTransformer->expects($this->any())
            ->method('reverseTransform')
            ->willReturnCallback(
                function ($element) {
                    return 'transformed_' . $element;
                }
            );

        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider()
    {
        return [
            [null, new ArrayCollection()],
            ['', new ArrayCollection()],
            [[], new ArrayCollection()],
            [['element1', 'element2'], new ArrayCollection(['transformed_element1', 'transformed_element2'])],
        ];
    }
}
