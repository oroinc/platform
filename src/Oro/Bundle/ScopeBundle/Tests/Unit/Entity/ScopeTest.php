<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Entity;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class ScopeTest extends TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors(): void
    {
        $properties = [
            ['id', 42],
        ];

        $this->assertPropertyAccessors(new Scope(), $properties);
    }
}
