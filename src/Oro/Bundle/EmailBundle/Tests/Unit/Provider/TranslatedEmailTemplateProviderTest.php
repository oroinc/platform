<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateTranslationResolver;
use Oro\Bundle\EmailBundle\Provider\TranslatedEmailTemplateProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class TranslatedEmailTemplateProviderTest extends TestCase
{
    private TranslatedEmailTemplateProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $propertyAccessor = new PropertyAccessor();
        $translationResolver = new EmailTemplateTranslationResolver($propertyAccessor);

        $this->provider = new TranslatedEmailTemplateProvider($propertyAccessor);
        $this->provider->setEmailTemplateTranslationResolver($translationResolver);
    }

    public function testGetTranslatedEmailTemplateWhenNoTranslations(): void
    {
        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content');

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateEntity->getSubject())
            ->setContent($emailTemplateEntity->getContent());

        $localization42 = $this->createLocalization(42);

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization42)
        );
    }

    public function testGetTranslatedEmailTemplateWhenOneTranslationAndFallback(): void
    {
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation1 = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content')
            ->addTranslation($emailTemplateTranslation1);

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateEntity->getSubject())
            ->setContent($emailTemplateEntity->getContent());

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization42)
        );
    }

    public function testGetTranslatedEmailTemplateWhenOneTranslationAndNotFallback(): void
    {
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation1 = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubject('Sample subject #42')
            ->setSubjectFallback(false)
            ->setContent('Sample content #42')
            ->setContentFallback(false);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content')
            ->addTranslation($emailTemplateTranslation1);

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateTranslation1->getSubject())
            ->setContent($emailTemplateTranslation1->getContent());

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization42)
        );
    }

    public function testGetTranslatedEmailTemplateWhenTwoTranslationsAndFirstFallback(): void
    {
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation1 = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubject('Sample subject #42')
            ->setSubjectFallback(false)
            ->setContent('Sample content #42')
            ->setContentFallback(false);

        $localization43 = $this->createLocalization(43, $localization42);
        $emailTemplateTranslation2 = (new EmailTemplateTranslation())
            ->setLocalization($localization43)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content')
            ->addTranslation($emailTemplateTranslation1)
            ->addTranslation($emailTemplateTranslation2);

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateTranslation1->getSubject())
            ->setContent($emailTemplateTranslation1->getContent());

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization43)
        );
    }

    public function testGetTranslatedEmailTemplateWhenTwoTranslationsAndBothFallback(): void
    {
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation1 = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $localization43 = $this->createLocalization(43, $localization42);
        $emailTemplateTranslation2 = (new EmailTemplateTranslation())
            ->setLocalization($localization43)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content')
            ->addTranslation($emailTemplateTranslation1)
            ->addTranslation($emailTemplateTranslation2);

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateEntity->getSubject())
            ->setContent($emailTemplateEntity->getContent());

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization43)
        );
    }

    public function testGetTranslatedEmailTemplateWhenTwoTranslationsAndRecursiveLocalizations(): void
    {
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation1 = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $localization43 = $this->createLocalization(43, $localization42);

        // Causes infinite recursion of localizations.
        $localization42->setParentLocalization($localization43);

        $emailTemplateTranslation2 = (new EmailTemplateTranslation())
            ->setLocalization($localization43)
            ->setSubjectFallback(true)
            ->setContentFallback(true);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content')
            ->addTranslation($emailTemplateTranslation1)
            ->addTranslation($emailTemplateTranslation2);

        $emailTemplateModel = (new EmailTemplateModel())
            ->setSubject($emailTemplateEntity->getSubject())
            ->setContent($emailTemplateEntity->getContent());

        self::assertEquals(
            $emailTemplateModel,
            $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization43)
        );
    }

    public function testGetTranslatedEmailTemplateWithAttachments(): void
    {
        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content');

        // Add default attachments
        $defaultAttachment1 = new EmailTemplateAttachment();
        $defaultAttachment1->setFilePlaceholder('entity.avatar');
        $emailTemplateEntity->addAttachment($defaultAttachment1);

        $defaultAttachment2 = new EmailTemplateAttachment();
        $defaultAttachment2->setFilePlaceholder('entity.file');
        $emailTemplateEntity->addAttachment($defaultAttachment2);

        $localization42 = $this->createLocalization(42);

        $translatedTemplate = $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization42);

        // Verify attachments are properly translated
        $attachments = $translatedTemplate->getAttachments();
        self::assertCount(2, $attachments);

        self::assertEquals($defaultAttachment1->getFilePlaceholder(), $attachments[0]->getFilePlaceholder());
        self::assertEquals($defaultAttachment2->getFilePlaceholder(), $attachments[1]->getFilePlaceholder());
    }

    public function testGetTranslatedEmailTemplateWithTranslatedAttachments(): void
    {
        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content');

        // Add default attachment
        $defaultAttachment = new EmailTemplateAttachment();
        $defaultAttachment->setFilePlaceholder('entity.avatar');
        $emailTemplateEntity->addAttachment($defaultAttachment);

        // Create translation
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubject('Translated subject')
            ->setSubjectFallback(false)
            ->setContent('Translated content')
            ->setContentFallback(false)
            ->setAttachmentsFallback(false); // Use translated attachments

        // Add translated attachment
        $translatedAttachment = new EmailTemplateAttachment();
        $translatedAttachment->setFilePlaceholder('entity.file');
        $translatedAttachment->setTranslation($emailTemplateTranslation);

        $emailTemplateTranslation->addAttachment($translatedAttachment);
        $emailTemplateEntity->addTranslation($emailTemplateTranslation);

        $translatedTemplate = $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization42);

        // Verify we get the translated attachment only
        $attachments = $translatedTemplate->getAttachments();
        self::assertCount(1, $attachments);

        $attachment = $attachments[0];
        self::assertEquals('entity.file', $attachment->getFilePlaceholder());
    }

    public function testGetTranslatedEmailTemplateWithAttachmentFallback(): void
    {
        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content');

        // Add default attachment
        $defaultAttachment = new EmailTemplateAttachment();
        $defaultAttachment->setFilePlaceholder('entity.defaultFile');
        $emailTemplateEntity->addAttachment($defaultAttachment);

        // Create translation with attachment fallback enabled
        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubject('Translated subject')
            ->setSubjectFallback(false)
            ->setContent('Translated content')
            ->setContentFallback(false)
            ->setAttachmentsFallback(true); // Use default attachments

        $emailTemplateEntity->addTranslation($emailTemplateTranslation);

        $translatedTemplate = $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization42);

        // Verify we get the default attachment
        $attachments = $translatedTemplate->getAttachments();
        self::assertCount(1, $attachments);

        $attachment = $attachments[0];
        self::assertEquals('entity.defaultFile', $attachment->getFilePlaceholder());
    }

    public function testGetTranslatedEmailTemplateWithMultipleLevelTranslations(): void
    {
        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Default subject')
            ->setContent('Default content');

        // Add default attachment
        $defaultAttachment = new EmailTemplateAttachment();
        $defaultAttachment->setFilePlaceholder('entity.defaultFile');
        $emailTemplateEntity->addAttachment($defaultAttachment);

        // Create parent localization and translation
        $parentLocalization = $this->createLocalization(42);
        $parentTranslation = (new EmailTemplateTranslation())
            ->setLocalization($parentLocalization)
            ->setSubject('Parent subject')
            ->setSubjectFallback(false)
            ->setContent('Parent content')
            ->setContentFallback(false)
            ->setAttachmentsFallback(false); // Custom attachments

        // Add parent attachment
        $parentAttachment = new EmailTemplateAttachment();
        $parentAttachment->setFilePlaceholder('entity.parentFile');
        $parentAttachment->setTranslation($parentTranslation);

        $parentTranslation->addAttachment($parentAttachment);
        $emailTemplateEntity->addTranslation($parentTranslation);

        // Create child localization and translation
        $childLocalization = $this->createLocalization(43, $parentLocalization);
        $childTranslation = (new EmailTemplateTranslation())
            ->setLocalization($childLocalization)
            ->setSubject('Child subject')
            ->setSubjectFallback(false)
            ->setContentFallback(true) // Fallback to parent content
            ->setAttachmentsFallback(true); // Fallback to parent attachments

        $emailTemplateEntity->addTranslation($childTranslation);

        $translatedTemplate = $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $childLocalization);

        // Verify subject is from child, content from parent, and attachments from parent
        self::assertEquals('Child subject', $translatedTemplate->getSubject());
        self::assertEquals('Parent content', $translatedTemplate->getContent());

        $attachments = $translatedTemplate->getAttachments();
        self::assertCount(1, $attachments);
        $attachment = $attachments[0];
        self::assertEquals('entity.parentFile', $attachment->getFilePlaceholder());
    }

    public function testGetTranslatedEmailTemplateWithCustomTranslatableFields(): void
    {
        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Default subject')
            ->setContent('Default content');

        // Add default attachment
        $defaultAttachment = new EmailTemplateAttachment();
        $defaultAttachment->setFilePlaceholder('entity.defaultFile');
        $emailTemplateEntity->addAttachment($defaultAttachment);

        $localization = $this->createLocalization(42);

        // Set custom translatable fields (exclude attachments)
        $this->provider->setTranslatableFields(['subject', 'content']);

        $translatedTemplate = $this->provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization);

        // Verify subject and content are translated but attachments are not included
        self::assertEquals('Default subject', $translatedTemplate->getSubject());
        self::assertEquals('Default content', $translatedTemplate->getContent());
        self::assertEmpty($translatedTemplate->getAttachments());
    }

    public function testGetTranslatedEmailTemplateWithNullTranslationResolver(): void
    {
        $propertyAccessor = new PropertyAccessor();
        $provider = new TranslatedEmailTemplateProvider($propertyAccessor);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Sample default subject')
            ->setContent('Sample default content');

        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubject('Translated subject')
            ->setSubjectFallback(false)
            ->setContent('Translated content')
            ->setContentFallback(false);

        $emailTemplateEntity->addTranslation($emailTemplateTranslation);

        $translatedTemplate = $provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization42);

        // Verify BC layer works correctly
        self::assertEquals('Translated subject', $translatedTemplate->getSubject());
        self::assertEquals('Translated content', $translatedTemplate->getContent());
        self::assertEquals($emailTemplateEntity->getName(), $translatedTemplate->getName());
        self::assertEquals($emailTemplateEntity->getEntityName(), $translatedTemplate->getEntityName());
        self::assertEquals($emailTemplateEntity->getType(), $translatedTemplate->getType());
    }

    public function testGetTranslatedEmailTemplateWithNullResolverAndAttachments(): void
    {
        $propertyAccessor = new PropertyAccessor();
        $provider = new TranslatedEmailTemplateProvider($propertyAccessor);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Default subject')
            ->setContent('Default content');

        // Add default attachment
        $defaultAttachment = new EmailTemplateAttachment();
        $defaultAttachment->setFilePlaceholder('entity.defaultFile');
        $emailTemplateEntity->addAttachment($defaultAttachment);

        $localization42 = $this->createLocalization(42);
        $emailTemplateTranslation = (new EmailTemplateTranslation())
            ->setLocalization($localization42)
            ->setSubject('Translated subject')
            ->setSubjectFallback(false)
            ->setContent('Translated content')
            ->setContentFallback(false)
            ->setAttachmentsFallback(false);

        // Add translated attachment
        $translatedAttachment = new EmailTemplateAttachment();
        $translatedAttachment->setFilePlaceholder('entity.translatedFile');
        $translatedAttachment->setTranslation($emailTemplateTranslation);

        $emailTemplateTranslation->addAttachment($translatedAttachment);
        $emailTemplateEntity->addTranslation($emailTemplateTranslation);

        $translatedTemplate = $provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization42);

        // Verify BC layer processes attachments correctly
        self::assertEquals('Translated subject', $translatedTemplate->getSubject());
        self::assertEquals('Translated content', $translatedTemplate->getContent());

        $attachments = $translatedTemplate->getAttachments();
        self::assertCount(1, $attachments);
        self::assertEquals('entity.translatedFile', $attachments[0]->getFilePlaceholder());
    }

    public function testGetTranslatedEmailTemplateWithNullResolverAndMultipleTranslations(): void
    {
        $propertyAccessor = new PropertyAccessor();
        $provider = new TranslatedEmailTemplateProvider($propertyAccessor);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Default subject')
            ->setContent('Default content');

        // Create parent localization and translation
        $parentLocalization = $this->createLocalization(42);
        $parentTranslation = (new EmailTemplateTranslation())
            ->setLocalization($parentLocalization)
            ->setSubject('Parent subject')
            ->setSubjectFallback(false)
            ->setContent('Parent content')
            ->setContentFallback(false);

        $emailTemplateEntity->addTranslation($parentTranslation);

        // Create child localization and translation
        $childLocalization = $this->createLocalization(43, $parentLocalization);
        $childTranslation = (new EmailTemplateTranslation())
            ->setLocalization($childLocalization)
            ->setSubject('Child subject')
            ->setSubjectFallback(false)
            ->setContentFallback(true); // Fallback to parent

        $emailTemplateEntity->addTranslation($childTranslation);

        $translatedTemplate = $provider->getTranslatedEmailTemplate($emailTemplateEntity, $childLocalization);

        // Verify BC layer handles fallback chain correctly
        self::assertEquals('Child subject', $translatedTemplate->getSubject());
        self::assertEquals('Parent content', $translatedTemplate->getContent());
    }

    public function testGetTranslatedEmailTemplateWithNullResolverAndCustomTranslatableFields(): void
    {
        $propertyAccessor = new PropertyAccessor();
        $provider = new TranslatedEmailTemplateProvider($propertyAccessor);

        // Set custom translatable fields (exclude attachments)
        $provider->setTranslatableFields(['subject', 'content']);

        $emailTemplateEntity = (new EmailTemplateEntity())
            ->setSubject('Default subject')
            ->setContent('Default content');

        // Add attachment that should be ignored
        $attachment = new EmailTemplateAttachment();
        $attachment->setFilePlaceholder('entity.file');
        $emailTemplateEntity->addAttachment($attachment);

        $localization = $this->createLocalization(42);

        $translatedTemplate = $provider->getTranslatedEmailTemplate($emailTemplateEntity, $localization);

        // Verify only specified fields are processed
        self::assertEquals('Default subject', $translatedTemplate->getSubject());
        self::assertEquals('Default content', $translatedTemplate->getContent());
        self::assertEmpty($translatedTemplate->getAttachments());
    }

    private function createLocalization(int $id, ?Localization $parentLocalization = null): Localization
    {
        $localization = new LocalizationStub($id);
        if ($parentLocalization !== null) {
            $localization->setParentLocalization($parentLocalization);
        }

        return $localization;
    }
}
