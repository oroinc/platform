<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

final class EmailTemplateTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testConstruct(): void
    {
        $template = new EmailTemplateModel('update_entity.html.twig', 'Sample content');

        self::assertSame('update_entity.html.twig', $template->getName());
        self::assertSame('Sample content', $template->getContent());
        self::assertSame(EmailTemplateInterface::TYPE_HTML, $template->getType());
    }

    public function testProperties(): void
    {
        $template = new EmailTemplateModel();
        self::assertPropertyAccessors($template, [
            ['name', 'sample_name', false],
            ['subject', 'Sample Subject'],
            ['content', 'Sample content', false],
            ['entityName', User::class],
            ['type', EmailTemplateInterface::TYPE_HTML],
        ]);
    }

    public function testAttachmentAccessors(): void
    {
        $template = new EmailTemplateModel();
        $attachment1 = new EmailTemplateAttachmentModel();
        $attachment2 = new EmailTemplateAttachmentModel();

        // Initially empty
        self::assertIsIterable($template->getAttachments());
        self::assertCount(0, $template->getAttachments());

        // Add attachments
        $template->addAttachment($attachment1);
        $template->addAttachment($attachment2);

        $attachments = $template->getAttachments();
        self::assertContains($attachment1, $attachments);
        self::assertContains($attachment2, $attachments);

        // Remove one
        $template->removeAttachment($attachment1);
        $attachments = $template->getAttachments();
        self::assertNotContains($attachment1, $attachments);
        self::assertContains($attachment2, $attachments);

        // Set attachments directly
        $attachment3 = new EmailTemplateAttachmentModel();
        $template->setAttachments([$attachment3]);
        $attachments = $template->getAttachments();
        self::assertCount(1, $attachments);
        self::assertContains($attachment3, $attachments);
        self::assertNotContains($attachment2, $attachments);
    }
}
