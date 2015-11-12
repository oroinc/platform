<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class RestFilterValueAccessorTest extends \PHPUnit_Framework_TestCase
{
    public function testAccessor()
    {
        $queryStringValues = [
            'prm1=val1'          => ['prm1', '=', 'val1'],
            'prm2<>val2'         => ['prm2', '<>', 'val2'],
            'prm3<val3'          => ['prm3', '<', 'val3'],
            'prm4<=val4'         => ['prm4', '<=', 'val4'],
            'prm5>val5'          => ['prm5', '>', 'val5'],
            'prm6>=val6'         => ['prm6', '>=', 'val6'],
            'prm7%3C%3Eval7'     => ['prm7', '<>', 'val7'],
            'prm8%3Cval8'        => ['prm8', '<', 'val8'],
            'prm9%3C=val9'       => ['prm9', '<=', 'val9'],
            'prm10%3Eval10'      => ['prm10', '>', 'val10'],
            'prm11%3E=val11'     => ['prm11', '>=', 'val11'],
            'prm12<><val12>'     => ['prm12', '<>', '<val12>'],
            'prm_13=%3Cval13%3E' => ['prm_13', '=', '<val13>'],
            'page[number]=123'   => ['page[number]', '=', '123'],
            'page%5Bsize%5D=456' => ['page[size]', '=', '456'],
        ];

        $request = Request::create('http://test.com?' . implode('&', array_keys($queryStringValues)));

        $accessor = new RestFilterValueAccessor($request);

        foreach ($queryStringValues as $key => $item) {
            list($name, $operator, $value) = $item;
            $this->assertTrue($accessor->has($name), sprintf('has - %s', $key));
            $filterValue = $accessor->get($name);
            $this->assertEquals($operator, $filterValue->getOperator(), sprintf('operator - %s', $key));
            $this->assertEquals($value, $filterValue->getValue(), sprintf('value - %s', $key));
        }

        $this->assertFalse($accessor->has('unknown'), 'has - unknown');
        $this->assertNull($accessor->get('unknown'), 'get - unknown');
    }
}
