<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Twig\SecurityPolicy;

use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\EmailTemplateSecurityPolicyChecker;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class EmailTemplateSecurityPolicyCheckOrderTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testRenderSucceedsAfterSecurityCheckRunsFirstInTheProcess(): void
    {
        /** @var EmailTemplateSecurityPolicyChecker $checker */
        $checker = self::getContainer()->get('oro_email.twig.security_policy.email_template_checker');

        $template = new EmailTemplateModel();
        $template->setName(null);
        $template->setSubject('');
        $template->setContent('');

        $violations = $checker->checkSecurityPolicy($template);
        self::assertSame([], $violations);

        /** @var EmailRenderer $renderer */
        $renderer = self::getContainer()->get('oro_email.email_renderer');

        $result = $renderer->renderTemplate('Hello {{ "<b>world</b>"|oro_html_sanitize }}');

        self::assertSame('Hello <b>world</b>', $result);
    }
}
