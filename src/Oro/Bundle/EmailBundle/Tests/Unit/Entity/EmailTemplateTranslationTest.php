<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class EmailTemplateTranslationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $this->assertPropertyAccessors(new EmailTemplateTranslation(), [
            ['id', 1],
            ['template', new EmailTemplate()],
            ['localization', new Localization()],
            ['subject', 'Test subject'],
            ['subjectFallback', false],
            ['content', 'Test content'],
            ['contentFallback', false],
        ]);
    }
}
