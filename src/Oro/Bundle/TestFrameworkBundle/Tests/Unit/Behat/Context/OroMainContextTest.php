<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;

class OroMainContextTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider skipWaitProvider
     * @param int $isMatch
     * @param string $step
     */
    public function testSkipWait($isMatch, $step)
    {
        $this->assertSame($isMatch, preg_match(OroMainContext::SKIP_WAIT_PATTERN, $step));
    }

    public function skipWaitProvider()
    {
        return [
            ['isMatched' => 1, 'step' => 'I should see "Bla Bla" flash message'],
            ['isMatched' => 1, 'step' => 'I should see "Bla Bla" error message'],
            ['isMatched' => 1, 'step' => 'I should see Schema updated flash message'],
            ['isMatched' => 0, 'step' => 'click Edit User in grid'],
            ['isMatched' => 0, 'step' => 'I save and close form'],
            ['isMatched' => 0, 'step' => 'I click "Update task" on "Contact with Charlie" in activity list'],
        ];
    }
}
