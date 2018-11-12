<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\CaseInsensitiveParameterBag;

class CaseInsensitiveParameterBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var CaseInsensitiveParameterBag */
    private $caseInsensitiveParameterBag;

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

        self::assertTrue($this->caseInsensitiveParameterBag->has($key));
        self::assertTrue($this->caseInsensitiveParameterBag->has(strtoupper($key)));
        self::assertTrue($this->caseInsensitiveParameterBag->has(ucfirst($key)));
        self::assertTrue($this->caseInsensitiveParameterBag->has(ucwords($key)));

        self::assertSame($value, $this->caseInsensitiveParameterBag->get($key));
        self::assertSame($value, $this->caseInsensitiveParameterBag->get(strtoupper($key)));
        self::assertSame($value, $this->caseInsensitiveParameterBag->get(ucfirst($key)));
        self::assertSame($value, $this->caseInsensitiveParameterBag->get(ucwords($key)));

        self::assertCount(1, $this->caseInsensitiveParameterBag->toArray());

        $this->caseInsensitiveParameterBag->remove($key);

        self::assertCount(0, $this->caseInsensitiveParameterBag->toArray());
    }

    public function caseDataProvider()
    {
        return [
            [
                'key'         => 'key1',
                'value'       => 'value1'
            ],
            [
                'key'         => 'KeY2',
                'value'       => 'value2'
            ],
            [
                'key'         => 'three words key',
                'value'       => 'value3'
            ],
            [
                'key'         => 'SoMe KeY',
                'value'       => 'value4'
            ]
        ];
    }
}
