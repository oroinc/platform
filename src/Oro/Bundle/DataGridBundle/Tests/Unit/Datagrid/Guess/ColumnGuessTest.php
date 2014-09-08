<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\Guess;

use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;

class ColumnGuessTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOptions()
    {
        $options    = ['test' => 123];
        $confidence = ColumnGuess::HIGH_CONFIDENCE;

        $guess = new ColumnGuess($options, $confidence);

        $this->assertEquals($options, $guess->getOptions());
        $this->assertEquals($confidence, $guess->getConfidence());
    }
}
