<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\TranslationBundle\Form\DataTransformer\CollectionToArrayTransformer;

class CollectionToArrayTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(ArrayCollection|array $data, array $result)
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
