<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Captcha;

use Oro\Bundle\FormBundle\Captcha\CaptchaProtectedFormsRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class CaptchaProtectedFormsRegistryTest extends TestCase
{
    public function testConstructorWithArray()
    {
        $forms = ['form1' => true, 'form2' => true];
        $registry = new CaptchaProtectedFormsRegistry($forms);

        $this->assertSame(['form1', 'form2'], $registry->getProtectedForms());
    }

    public function testConstructorWithTraversable()
    {
        $forms = new \ArrayIterator(
            ['form1' => $this->createMock(FormInterface::class), 'form2' => $this->createMock(FormInterface::class)]
        );
        $registry = new CaptchaProtectedFormsRegistry($forms);

        $this->assertSame(['form1', 'form2'], $registry->getProtectedForms());
    }

    public function testProtectForm()
    {
        $registry = new CaptchaProtectedFormsRegistry([]);
        $registry->protectForm('form1');

        $this->assertSame(['form1'], $registry->getProtectedForms());

        $registry->protectForm('form2');

        $this->assertSame(['form1', 'form2'], $registry->getProtectedForms());
    }
}
