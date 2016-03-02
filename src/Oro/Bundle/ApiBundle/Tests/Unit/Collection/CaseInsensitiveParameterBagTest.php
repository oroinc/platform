<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\CaseInsensitiveParameterBag;

class CaseInsensitiveParameterBagTest extends \PHPUnit_Framework_TestCase
{
    /** @var CaseInsensitiveParameterBag */
    protected $caseInsensitiveParameterBag;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->caseInsensitiveParameterBag = new CaseInsensitiveParameterBag();
    }

    /**
     * @dataProvider caseDataProvider
     *
     * @param string $key
     * @param string $value
     */
    public function testActions($key, $value)
    {
        $this->caseInsensitiveParameterBag->set($key, $value);
        $this->caseInsensitiveParameterBag->set(strtoupper($key), $value);
        $this->caseInsensitiveParameterBag->set(ucfirst($key), $value);
        $this->caseInsensitiveParameterBag->set(ucwords($key), $value);

        $this->assertTrue($this->caseInsensitiveParameterBag->has($key));
        $this->assertTrue($this->caseInsensitiveParameterBag->has(strtoupper($key)));
        $this->assertTrue($this->caseInsensitiveParameterBag->has(ucfirst($key)));
        $this->assertTrue($this->caseInsensitiveParameterBag->has(ucwords($key)));

        $this->assertSame($value, $this->caseInsensitiveParameterBag->get($key));
        $this->assertSame($value, $this->caseInsensitiveParameterBag->get(strtoupper($key)));
        $this->assertSame($value, $this->caseInsensitiveParameterBag->get(ucfirst($key)));
        $this->assertSame($value, $this->caseInsensitiveParameterBag->get(ucwords($key)));

        $this->assertCount(1, $this->caseInsensitiveParameterBag->toArray());

        $this->caseInsensitiveParameterBag->remove($key);

        $this->assertCount(0, $this->caseInsensitiveParameterBag->toArray());
    }

    public function caseDataProvider()
    {
        return [
            [
                'key'         => 'key1',
                'value'       => 'value1',
            ],
            [
                'key'         => 'KeY2',
                'value'       => 'value2',
            ],
            [
                'key'         => 'three words key',
                'value'       => 'value3',
            ],
            [
                'key'         => 'SoMe KeY',
                'value'       => 'value4',
            ]
        ];
    }
}
