<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Symfony\Component\Form\DataTransformerInterface;

class CollectionToArrayTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DataTransformerInterface */
    private $elementTransformer;

    /** @var CollectionToArrayTransformer */
    private $transformer;

    protected function setUp()
    {
        $this->elementTransformer = $this->createMock(DataTransformerInterface::class);

        $this->transformer = new CollectionToArrayTransformer($this->elementTransformer);
    }

    public function testTransform()
    {
        self::assertNull($this->transformer->transform(new ArrayCollection()));
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($value, $expected)
    {
        $this->elementTransformer->expects(self::any())
            ->method('reverseTransform')
            ->willReturnCallback(
                function ($element) {
                    return 'transformed_' . $element;
                }
            );

        self::assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider()
    {
        return [
            [null, new ArrayCollection()],
            ['', new ArrayCollection()],
            [[], new ArrayCollection()],
            [['element1', 'element2'], new ArrayCollection(['transformed_element1', 'transformed_element2'])]
        ];
    }
}
