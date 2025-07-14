<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\Guess;

use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use PHPUnit\Framework\TestCase;

class ColumnGuessTest extends TestCase
{
    public function testGetOptions(): void
    {
        $options = ['test' => 123];
        $confidence = ColumnGuess::HIGH_CONFIDENCE;

        $guess = new ColumnGuess($options, $confidence);

        $this->assertEquals($options, $guess->getOptions());
        $this->assertEquals($confidence, $guess->getConfidence());
    }
}
