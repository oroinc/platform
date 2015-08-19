<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\DefaultColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;

class DefaultColumnOptionsGuesserTest extends \PHPUnit_Framework_TestCase
{
    /** @var DefaultColumnOptionsGuesser */
    protected $guesser;

    protected function setUp()
    {
        $this->guesser = new DefaultColumnOptionsGuesser();
    }

    /**
     * @dataProvider guessFormatterProvider
     */
    public function testGuessFormatter($type, $expected)
    {
        $guess = $this->guesser->guessFormatter('TestClass', 'testProp', $type);
        $this->assertEquals($expected, $guess->getOptions());
        $this->assertEquals(ColumnGuess::LOW_CONFIDENCE, $guess->getConfidence());
    }

    public function guessFormatterProvider()
    {
        return [
            ['integer', ['frontend_type' => Property::TYPE_INTEGER]],
            ['smallint', ['frontend_type' => Property::TYPE_INTEGER]],
            ['bigint', ['frontend_type' => Property::TYPE_INTEGER]],
            ['decimal', ['frontend_type' => Property::TYPE_DECIMAL]],
            ['float', ['frontend_type' => Property::TYPE_DECIMAL]],
            ['boolean', ['frontend_type' => Property::TYPE_BOOLEAN]],
            ['date', ['frontend_type' => Property::TYPE_DATE]],
            ['datetime', ['frontend_type' => Property::TYPE_DATETIME]],
            ['time', ['frontend_type' => Property::TYPE_TIME]],
            ['money', ['frontend_type' => Property::TYPE_CURRENCY]],
            ['percent', ['frontend_type' => Property::TYPE_PERCENT]],
            ['string', ['frontend_type' => Property::TYPE_STRING]],
            ['other', ['frontend_type' => Property::TYPE_STRING]],
        ];
    }
}
