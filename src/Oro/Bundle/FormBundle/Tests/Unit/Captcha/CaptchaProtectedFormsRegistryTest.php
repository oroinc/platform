<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Captcha;

use Oro\Bundle\FormBundle\Captcha\CaptchaProtectedFormsRegistry;
use PHPUnit\Framework\TestCase;

class CaptchaProtectedFormsRegistryTest extends TestCase
{
    public function testProtectForm(): void
    {
        $registry = new CaptchaProtectedFormsRegistry(['form1']);

        $this->assertSame(['form1'], $registry->getProtectedForms());

        $registry->protectForm('form2');

        $this->assertSame(['form1', 'form2'], $registry->getProtectedForms());
    }
}
