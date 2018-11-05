<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\EmailBundle\Form\Extension\EmailTypeTemplateAccessibilityCheckerExtension;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormTypeExtensionInterface;

class EmailTypeTemplateAccessibilityCheckerExtensionTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testEmailFormHasExtension()
    {
        $extensions = array_map(function (FormTypeExtensionInterface $extension) {
            return get_class($extension);
        }, self::getContainer()->get('form.extension')->getTypeExtensions(EmailType::class));

        $this->assertContains(EmailTypeTemplateAccessibilityCheckerExtension::class, $extensions);
    }
}
