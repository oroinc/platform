<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateTranslationResolver;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailTemplateTranslationResolverTest extends TestCase
{
    private EmailTemplateTranslationResolver $resolver;

    protected function setUp(): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->resolver = new EmailTemplateTranslationResolver($propertyAccessor);
    }

    public function testResolveTranslationDefault(): void
    {
        $template = new EmailTemplate();
        $template->setSubject('Default subject');

        $result = $this->resolver->resolveTranslation($template, 'subject', null);
        self::assertEquals('Default subject', $result);
    }

    public function testResolveTranslationWithExplicitTranslation(): void
    {
        $template = new EmailTemplate();
        $localization = new Localization();
        ReflectionUtil::setId($localization, 1);

        $translation = new EmailTemplateTranslation();
        $translation->setLocalization($localization);
        $translation->setSubject('Localized subject');
        $translation->setSubjectFallback(false);
        $template->addTranslation($translation);

        $result = $this->resolver->resolveTranslation($template, 'subject', $localization);
        self::assertEquals('Localized subject', $result);
    }

    public function testResolveTranslationWithFallbackToDefault(): void
    {
        $template = new EmailTemplate();
        $template->setSubject('Default subject');
        $localization = new Localization();
        ReflectionUtil::setId($localization, 1);

        $translation = new EmailTemplateTranslation();
        $translation->setLocalization($localization);
        $translation->setSubject('Should not be used');
        $translation->setSubjectFallback(true);
        $template->addTranslation($translation);

        $result = $this->resolver->resolveTranslation($template, 'subject', $localization);
        self::assertEquals('Default subject', $result);
    }

    public function testResolveTranslationWithLocalizationTree(): void
    {
        $template = new EmailTemplate();
        $template->setSubject('Default subject');
        $parentLoc = new Localization();
        ReflectionUtil::setId($parentLoc, 10);
        $childLoc = new Localization();
        ReflectionUtil::setId($childLoc, 20);
        $childLoc->setParentLocalization($parentLoc);

        $parentTranslation = new EmailTemplateTranslation();
        $parentTranslation->setLocalization($parentLoc);
        $parentTranslation->setSubject('Parent subject');
        $parentTranslation->setSubjectFallback(false);

        $childTranslation = new EmailTemplateTranslation();
        $childTranslation->setLocalization($childLoc);
        $childTranslation->setSubject('Child subject');
        $childTranslation->setSubjectFallback(true);

        $template->addTranslation($parentTranslation);
        $template->addTranslation($childTranslation);

        $result = $this->resolver->resolveTranslation($template, 'subject', $childLoc);
        self::assertEquals('Parent subject', $result);
    }

    public function testResolveTranslationWithAttachments(): void
    {
        $template = new EmailTemplate();

        $file = new File();
        $file->setFilename('document.pdf');
        $file->setMimeType('application/pdf');

        $attachment = new EmailTemplateAttachment();
        ReflectionUtil::setId($attachment, 123);
        $attachment->setFile($file);
        $attachment->setFilePlaceholder('{{ entity.document }}');
        $template->addAttachment($attachment);

        $result = $this->resolver->resolveTranslation($template, 'attachments', null);

        self::assertInstanceOf(ArrayCollection::class, $result);
        self::assertCount(1, $result);

        $resolvedAttachment = $result->first();
        self::assertInstanceOf(EmailTemplateAttachmentModel::class, $resolvedAttachment);
        self::assertEquals(123, $resolvedAttachment->getId());
        self::assertSame($file, $resolvedAttachment->getFile());
        self::assertEquals('{{ entity.document }}', $resolvedAttachment->getFilePlaceholder());
    }

    public function testResolveTranslationWithLocalizedAttachments(): void
    {
        $template = new EmailTemplate();

        // Default attachment
        $defaultFile = new File();
        $defaultFile->setFilename('document.pdf');
        $defaultFile->setMimeType('application/pdf');

        $defaultAttachment = new EmailTemplateAttachment();
        ReflectionUtil::setId($defaultAttachment, 456);
        $defaultAttachment->setFile($defaultFile);
        $defaultAttachment->setFilePlaceholder('{{ entity.document }}');
        $template->addAttachment($defaultAttachment);

        // Create localization
        $localization = new Localization();
        ReflectionUtil::setId($localization, 42);

        // Create translated template with no fallback for attachments
        $translation = new EmailTemplateTranslation();
        $translation->setLocalization($localization);
        $translation->setSubject('Localized subject');
        $translation->setSubjectFallback(false);
        $translation->setAttachmentsFallback(false);
        $template->addTranslation($translation);

        // Create localized attachment
        $localizedFile = new File();
        $localizedFile->setFilename('localized-document.pdf');
        $localizedFile->setMimeType('application/pdf');

        $localizedAttachment = new EmailTemplateAttachment();
        ReflectionUtil::setId($localizedAttachment, 789);
        $localizedAttachment->setFile($localizedFile);
        $localizedAttachment->setFilePlaceholder('{{ entity.localizedDocument }}');
        $translation->addAttachment($localizedAttachment);

        // Test with specific localization - should get localized attachments
        $result = $this->resolver->resolveTranslation($template, 'attachments', $localization);
        self::assertInstanceOf(ArrayCollection::class, $result);
        self::assertCount(1, $result);

        $resolvedAttachment = $result->first();
        self::assertInstanceOf(EmailTemplateAttachmentModel::class, $resolvedAttachment);
        self::assertEquals(789, $resolvedAttachment->getId());
        self::assertSame($localizedFile, $resolvedAttachment->getFile());
        self::assertEquals('{{ entity.localizedDocument }}', $resolvedAttachment->getFilePlaceholder());
    }

    public function testResolveTranslationWithAttachmentsFallback(): void
    {
        $template = new EmailTemplate();

        // Default attachment
        $defaultFile = new File();
        $defaultFile->setFilename('document.pdf');

        $defaultAttachment = new EmailTemplateAttachment();
        ReflectionUtil::setId($defaultAttachment, 111);
        $defaultAttachment->setFile($defaultFile);
        $template->addAttachment($defaultAttachment);

        // Create localization
        $localization = new Localization();
        ReflectionUtil::setId($localization, 50);

        // Create translation with attachments fallback enabled
        $translation = new EmailTemplateTranslation();
        $translation->setLocalization($localization);
        $translation->setAttachmentsFallback(true); // Falls back to default
        $template->addTranslation($translation);

        // Test with specific localization - should fall back to default attachments
        $result = $this->resolver->resolveTranslation($template, 'attachments', $localization);
        self::assertInstanceOf(ArrayCollection::class, $result);
        self::assertCount(1, $result);

        $resolvedAttachment = $result->first();
        self::assertInstanceOf(EmailTemplateAttachmentModel::class, $resolvedAttachment);
        self::assertEquals(111, $resolvedAttachment->getId());
        self::assertSame($defaultFile, $resolvedAttachment->getFile());
    }

    public function testResolveTranslationWithEmptyAttachments(): void
    {
        $template = new EmailTemplate();
        // No attachments added

        $result = $this->resolver->resolveTranslation($template, 'attachments', null);

        self::assertInstanceOf(ArrayCollection::class, $result);
        self::assertCount(0, $result);
    }

    public function testResolveTranslationWithMultipleLocalizationLevels(): void
    {
        $template = new EmailTemplate();
        $template->setSubject('Default subject');
        $template->setContent('Default content');

        // Create root localization
        $rootLoc = new Localization();
        ReflectionUtil::setId($rootLoc, 1);

        // Create parent localization
        $parentLoc = new Localization();
        ReflectionUtil::setId($parentLoc, 2);
        $parentLoc->setParentLocalization($rootLoc);

        // Create child localization
        $childLoc = new Localization();
        ReflectionUtil::setId($childLoc, 3);
        $childLoc->setParentLocalization($parentLoc);

        // Root translation
        $rootTranslation = new EmailTemplateTranslation();
        $rootTranslation->setLocalization($rootLoc);
        $rootTranslation->setSubject('Root subject');
        $rootTranslation->setSubjectFallback(false);
        $rootTranslation->setContent('Root content');
        $rootTranslation->setContentFallback(false);

        // Parent translation with fallback subject
        $parentTranslation = new EmailTemplateTranslation();
        $parentTranslation->setLocalization($parentLoc);
        $parentTranslation->setSubject('Parent subject');
        $parentTranslation->setSubjectFallback(true); // Falls back to root
        $parentTranslation->setContent('Parent content');
        $parentTranslation->setContentFallback(false); // Does not fall back

        // Child translation with custom subject but fallback content
        $childTranslation = new EmailTemplateTranslation();
        $childTranslation->setLocalization($childLoc);
        $childTranslation->setSubject('Child subject');
        $childTranslation->setSubjectFallback(false); // Does not fall back
        $childTranslation->setContent('Child content');
        $childTranslation->setContentFallback(true); // Falls back to parent

        $template->addTranslation($rootTranslation);
        $template->addTranslation($parentTranslation);
        $template->addTranslation($childTranslation);

        // Test child localization
        $subjectResult = $this->resolver->resolveTranslation($template, 'subject', $childLoc);
        $contentResult = $this->resolver->resolveTranslation($template, 'content', $childLoc);

        self::assertEquals('Child subject', $subjectResult, 'Should use child subject directly');
        self::assertEquals('Parent content', $contentResult, 'Should fall back to parent content');

        // Test parent localization
        $subjectResult = $this->resolver->resolveTranslation($template, 'subject', $parentLoc);
        $contentResult = $this->resolver->resolveTranslation($template, 'content', $parentLoc);

        self::assertEquals('Root subject', $subjectResult, 'Should fall back to root subject');
        self::assertEquals('Parent content', $contentResult, 'Should use parent content directly');
    }

    public function testResolveTranslationWithNoTranslationsFound(): void
    {
        $template = new EmailTemplate();
        $template->setSubject('Default subject');
        $template->setContent('Default content');

        $localization = new Localization();
        ReflectionUtil::setId($localization, 999); // ID not present in any translation

        $result = $this->resolver->resolveTranslation($template, 'subject', $localization);
        self::assertEquals('Default subject', $result, 'Should fall back to default when no translations are found');
    }

    public function testResolveTranslationWithNonExistentField(): void
    {
        $this->expectException(NoSuchPropertyException::class);

        $template = new EmailTemplate();
        $localization = new Localization();
        ReflectionUtil::setId($localization, 1);

        $this->resolver->resolveTranslation($template, 'nonExistentField', $localization);
    }
}
