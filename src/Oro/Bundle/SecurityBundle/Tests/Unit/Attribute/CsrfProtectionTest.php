<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute;

use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use PHPUnit\Framework\TestCase;

class CsrfProtectionTest extends TestCase
{
    public function testAttribute(): void
    {
        $attribute = new CsrfProtection(
            enabled: true,
            useRequest: true,
        );

        $this->assertTrue($attribute->isEnabled());
        $this->assertTrue($attribute->isUseRequest());
    }
}
