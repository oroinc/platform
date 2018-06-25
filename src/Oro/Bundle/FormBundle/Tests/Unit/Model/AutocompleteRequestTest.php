<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Model\AutocompleteRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AutocompleteRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param Request $request
     * @param array   $expected
     *
     * @dataProvider createDataProvider
     */
    public function testCreate(Request $request, array $expected)
    {
        $autocompleteRequest = new AutocompleteRequest($request);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($expected as $key => $field) {
            $this->assertEquals(
                $field,
                $propertyAccessor->getValue($autocompleteRequest, $key),
                sprintf('%s did not match', $key)
            );
        }
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'empty' => [
                new Request(),
                [
                    'name'         => null,
                    'per_page'     => 50,
                    'page'         => 1,
                    'query'        => null,
                    'search_by_id' => null,
                ]
            ],
            'data'  => [
                new Request(
                    [
                        'name'         => 'name',
                        'per_page'     => 10,
                        'page'         => 10,
                        'query'        => 'string',
                        'search_by_id' => false
                    ]
                ),
                [
                    'name'         => 'name',
                    'per_page'     => 10,
                    'page'         => 10,
                    'query'        => 'string',
                    'search_by_id' => false
                ]
            ]
        ];
    }
}
