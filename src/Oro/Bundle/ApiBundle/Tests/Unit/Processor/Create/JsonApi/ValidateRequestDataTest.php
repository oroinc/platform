<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Create\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi\ValidateRequestDataTest as ParentTest;

class ValidateRequestDataTest extends ParentTest
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function requestDataProvider()
    {
        return [
            [[], 'The primary data object should exist', '/data'],
            [['data' => null], 'The primary data object should not be empty', '/data'],
            [['data' => []], 'The primary data object should not be empty', '/data'],
            [['data' => ['attributes' => ['foo' => 'bar']]], 'The \'type\' parameter is required', '/data/type'],
            [
                ['data' => ['type' => 'test']],
                'The primary data object should contain \'attributes\' or \'relationships\' block',
                '/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test']],
                'The primary data object should contain \'attributes\' or \'relationships\' block',
                '/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'attributes' => null]],
                'The \'attributes\' parameter should be an array',
                '/data/attributes',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'attributes' => []]],
                'The \'attributes\' parameter should not be empty',
                '/data/attributes',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'attributes' => [1,2,3]]],
                'The \'attributes\' parameter should be an associative array',
                '/data/attributes',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => null]],
                'The \'relationships\' parameter should be an array',
                '/data/relationships',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => []]],
                'The \'relationships\' parameter should not be empty',
                '/data/relationships',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => ['test' => null]]],
                'Data object have no data',
                '/data/relationships/test/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => ['test' => ['data' => null]]]],
                'The primary data object should not be empty',
                '/data/relationships/test/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => ['test' => ['data' => []]]]],
                'The primary data object should not be empty',
                '/data/relationships/test/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => ['test' => ['data' => []]]]],
                'The primary data object should not be empty',
                '/data/relationships/test/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => ['test' => ['data' => ['id' => '2']]]]],
                'The \'type\' parameter is required',
                '/data/relationships/test/data/type',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' =>
                     [
                         'type' => 'test',
                         'relationships' => ['test' => ['data' => ['type' => 'test']]]
                     ]
                ],
                'The \'id\' parameter is required',
                '/data/relationships/test/data/id',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' =>
                     [
                         'type' => 'test',
                         'relationships' => ['test' => ['data' => [['id' => '2']]]]
                     ]
                ],
                'The \'type\' parameter is required',
                '/data/relationships/test/data/0/type',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' =>
                     [
                         'type' => 'test',
                         'relationships' => ['test' => ['data' => [['type' => 'test']]]]
                     ]
                ],
                'The \'id\' parameter is required',
                '/data/relationships/test/data/0/id',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ]
        ];
    }

    /**
     * @return ValidateRequestData
     */
    protected function getProcessor()
    {
        return new ValidateRequestData($this->valueNormalizer);
    }
}
