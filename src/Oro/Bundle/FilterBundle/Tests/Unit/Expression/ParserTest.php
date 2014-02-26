<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression;

use Oro\Bundle\FilterBundle\Expression\Date\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /** @var Parser */
    protected $parser;

    public function setUp()
    {
        $this->parser = new Parser();
    }

    public function tearDown()
    {
        unset($this->parser);
    }

    /**
     * @dataProvider parseDataProvider
     *
     * @param string $input
     * @param string $expectedOutput
     * @param null   $expectedException
     */
    public function parseTest($input, $expectedOutput, $expectedException = null)
    {
        if (null !== $expectedException) {
            $this->setExpectedException($expectedException);
        }

        $this->assertSame($expectedOutput, $this->parser->parse($input));
    }

    /**
     * @return array
     */
    public function parseDataProvider()
    {
        return [
            'should parse '
        ];
    }
}
