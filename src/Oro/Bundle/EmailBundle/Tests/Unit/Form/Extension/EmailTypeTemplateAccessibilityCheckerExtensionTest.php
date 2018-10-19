<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Extension\EmailTypeTemplateAccessibilityCheckerExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EmailTypeTemplateAccessibilityCheckerExtensionTest extends TypeTestCase
{
    public function testRemoveField()
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', 'entity:' . EmailTemplate::class)
            ->willReturn(false);

        $extension = new EmailTypeTemplateAccessibilityCheckerExtension($authorizationChecker);

        $this->builder->add('template');
        $extension->buildForm($this->builder, []);

        $this->assertFalse($this->builder->has('template'));
    }

    public function testSafeField()
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', 'entity:' . EmailTemplate::class)
            ->willReturn(true);

        $extension = new EmailTypeTemplateAccessibilityCheckerExtension($authorizationChecker);

        $this->builder->add('template');
        $extension->buildForm($this->builder, []);

        $this->assertTrue($this->builder->has('template'));
    }
}
