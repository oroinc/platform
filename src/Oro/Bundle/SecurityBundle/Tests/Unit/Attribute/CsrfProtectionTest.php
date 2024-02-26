<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute;

use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;

class CsrfProtectionTest extends \PHPUnit\Framework\TestCase
{
    public function testAttribute()
    {
        $attribute = new CsrfProtection(
            enabled: true,
            useRequest: true,
        );

        $this->assertTrue($attribute->isEnabled());
        $this->assertTrue($attribute->isUseRequest());
    }
}
