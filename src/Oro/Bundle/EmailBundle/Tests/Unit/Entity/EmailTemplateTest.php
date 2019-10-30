<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class EmailTemplateTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait, EntityTrait;

    public function testConstruct(): void
    {
        $template = new EmailTemplate('update_entity.html.twig', "@subject = sdfdsf\n abc");

        $this->assertSame('sdfdsf', $template->getSubject());
        $this->assertSame('abc', $template->getContent());

        // Default values
        $this->assertFalse($template->getIsSystem());
        $this->assertTrue($template->getIsEditable());
        $this->assertSame(EmailTemplate::TYPE_HTML, $template->getType());
    }

    public function testProperties(): void
    {
        $template = new EmailTemplate();
        $this->assertPropertyAccessors($template, [
            ['id', 1],
            ['isSystem', true, false],
            ['isEditable', true, false],
            ['name', 'test_name', false],
            ['parent', 42],
            ['subject', 'Default subject'],
            ['content', 'Default content', false],
            ['entityName', User::class],
            ['type', EmailTemplate::TYPE_HTML],
            ['owner', new User()],
            ['organization', new Organization()],
            ['visible', true]
        ]);

        $this->assertPropertyCollections($template, [
            ['translations', new EmailTemplateTranslation()],
        ]);
    }

    public function testClone(): void
    {
        $template = new EmailTemplate('original_name', 'original content', EmailTemplate::TYPE_TEXT, true);
        $template->setIsEditable(false);
        $this->setValue($template, 'id', 42);

        $originalLocalization = new EmailTemplateTranslation();
        $template->addTranslation($originalLocalization);

        $clone = clone $template;

        $this->assertNull($clone->getId());
        $this->assertEquals($clone->getParent(), $template->getId());
        $this->assertSame('original_name', $clone->getName());
        $this->assertSame('original content', $clone->getContent());
        $this->assertSame(EmailTemplate::TYPE_TEXT, $clone->getType());

        $this->assertFalse($clone->getIsSystem());
        $this->assertTrue($clone->getIsEditable());

        $clonedLocalization = $clone->getTranslations()->first();
        $this->assertNotSame($originalLocalization, $clonedLocalization);
        $this->assertSame($clone, $clonedLocalization->getTemplate());
    }

    public function testToString(): void
    {
        $template = new EmailTemplate('template_name');
        $this->assertSame('template_name', (string)$template);
    }
}
