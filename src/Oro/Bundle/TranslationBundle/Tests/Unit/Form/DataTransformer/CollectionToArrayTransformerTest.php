<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\TranslationBundle\Form\DataTransformer\CollectionToArrayTransformer;
use PHPUnit\Framework\TestCase;

class CollectionToArrayTransformerTest extends TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(ArrayCollection|array $data, array $result): void
    {
        $transformer = new CollectionToArrayTransformer();

        $this->assertEquals($result, $transformer->transform($data));
    }

    public function transformDataProvider(): array
    {
        $testArray = [1, 2, 3];

        return [
            'empty array' => [
                'data'   => [],
                'result' => [],
            ],
            'collection' => [
                'data'   => new ArrayCollection($testArray),
                'result' => $testArray
            ],
        ];
    }
}
