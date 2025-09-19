<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Captcha;

use Oro\Bundle\FormBundle\Captcha\CaptchaProtectedFormsRegistry;
use PHPUnit\Framework\TestCase;

class CaptchaProtectedFormsRegistryTest extends TestCase
{
    public function testProtectForm(): void
    {
        $registry = new CaptchaProtectedFormsRegistry(['form1' => 'all']);

        $this->assertSame(['form1'], $registry->getProtectedForms());

        $registry->protectForm('form2', 'all');

        $this->assertSame(['form1', 'form2'], $registry->getProtectedForms());
    }

    public function testProtectedFormsByScope(): void
    {
        $registry = new CaptchaProtectedFormsRegistry(
            ['form1' => 'all', 'form2' => 'global', 'form3' => 'all']
        );

        $this->assertEquals(['form1', 'form2', 'form3'], $registry->getProtectedFormsByScope('app'));
    }
}
