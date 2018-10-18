<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Entity;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ScopeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $properties = [
            ['id', 42],
        ];

        $this->assertPropertyAccessors(new Scope(), $properties);
    }
}
