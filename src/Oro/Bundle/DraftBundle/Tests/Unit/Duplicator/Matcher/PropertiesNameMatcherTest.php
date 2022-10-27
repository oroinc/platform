<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator\Matcher;

use Oro\Bundle\DraftBundle\Duplicator\Matcher\PropertiesNameMatcher;

class PropertiesNameMatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testMatches(): void
    {
        $matcher = new PropertiesNameMatcher(['field1']);

        $matches = $matcher->matches(null, 'field1');
        $this->assertTrue($matches);
        $matches = $matcher->matches(null, 'field2');
        $this->assertFalse($matches);
    }
}
