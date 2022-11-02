<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Annotation;

use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;

class CsrfProtectionTest extends \PHPUnit\Framework\TestCase
{
    public function testAnnotation()
    {
        $annotation = new CsrfProtection(
            [
                'enabled' => true,
                'useRequest' => true
            ]
        );
        $this->assertTrue($annotation->isEnabled());
        $this->assertTrue($annotation->isUseRequest());
    }
}
