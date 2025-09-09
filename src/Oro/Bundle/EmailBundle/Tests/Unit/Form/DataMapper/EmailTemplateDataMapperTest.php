<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\DataMapper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Form\DataMapper\EmailTemplateDataMapper;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateTranslationResolver;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailTemplateDataMapperTest extends TestCase
{
    private EmailTemplateDataMapper $dataMapper;

    private MockObject&EmailTemplateTranslationResolver $emailTemplateTranslationResolver;

    private MockObject&FileManager $fileManager;

    private MockObject&DataMapperInterface $innerDataMapper;

    private MockObject&FormInterface $translationsForm;

    private MockObject&FormInterface $otherForm;

    private \ArrayIterator $forms;

    protected function setUp(): void
    {
        $this->translationsForm = $this->createMock(FormInterface::class);
        $this->translationsForm
            ->expects(self::any())
            ->method('getName')
            ->willReturn('translations');

        $this->otherForm = $this->createMock(FormInterface::class);
        $this->otherForm
            ->expects(self::any())
            ->method('getName')
            ->willReturn('other_form');

        $this->forms = new \ArrayIterator([
            $this->otherForm,
            $this->translationsForm,
        ]);

        $this->emailTemplateTranslationResolver = $this->createMock(EmailTemplateTranslationResolver::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->innerDataMapper = $this->createMock(DataMapperInterface::class);

        $this->dataMapper = new EmailTemplateDataMapper(
            $this->emailTemplateTranslationResolver,
            $this->fileManager,
            $this->innerDataMapper
        );
    }

    private function createLocalization(int $id): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }

    private function createEmailTemplateTranslation(Localization $localization): EmailTemplateTranslation
    {
        return (new EmailTemplateTranslation())
            ->setLocalization($localization);
    }

    public function testMapDataToFormsWithNullViewData(): void
    {
        $this->innerDataMapper
            ->expects(self::never())
            ->method('mapDataToForms');

        $this->dataMapper->mapDataToForms(null, $this->forms);
    }

    public function testMapDataToFormsWithInvalidViewDataType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->dataMapper->mapDataToForms(new \stdClass(), $this->forms);
    }

    public function testMapDataToFormsSetsDefaultTranslation(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setSubject('Default subject');
        $emailTemplate->setContent('Default content');

        $defaultForm = $this->createMock(FormInterface::class);

        $this->translationsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn(['default' => $defaultForm]);

        $this->translationsForm
            ->expects(self::once())
            ->method('setData')
            ->with(
                self::callback(static function ($data) use ($emailTemplate) {
                    self::assertArrayHasKey('default', $data);
                    self::assertEquals($emailTemplate->getSubject(), $data['default']->getSubject());
                    self::assertEquals($emailTemplate->getContent(), $data['default']->getContent());
                    self::assertSame($emailTemplate, $data['default']->getTemplate());
                    self::assertFalse($data['default']->isSubjectFallback());
                    self::assertFalse($data['default']->isContentFallback());
                    self::assertFalse($data['default']->isAttachmentsFallback());

                    return true;
                })
            );

        $this->innerDataMapper
            ->expects(self::once())
            ->method('mapDataToForms')
            ->with(
                self::identicalTo($emailTemplate),
                self::callback(function ($forms) {
                    return count(iterator_to_array($forms)) === 1 && iterator_to_array($forms)[0] === $this->otherForm;
                })
            );

        $this->dataMapper->mapDataToForms($emailTemplate, $this->forms);
    }

    public function testMapDataToFormsSetsExplicitTranslation(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setSubject('Default subject');
        $emailTemplate->setContent('Default content');

        $localization1 = $this->createLocalization(1);
        $translation1 = $this->createEmailTemplateTranslation($localization1);
        $translation1->setSubject('Translation1 subject');
        $translation1->setContent('Translation1 content');
        $translation1->setSubjectFallback(false);
        $translation1->setContentFallback(false);
        $translation1->setAttachmentsFallback(false);
        $emailTemplate->addTranslation($translation1);

        $defaultForm = $this->createMock(FormInterface::class);
        $translationForm1 = $this->createMock(FormInterface::class);

        $this->translationsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([
                'default' => $defaultForm,
                (string)$localization1->getId() => $translationForm1,
            ]);

        $this->translationsForm
            ->expects(self::once())
            ->method('setData')
            ->with(
                self::callback(static function ($data) use ($translation1, $localization1) {
                    self::assertArrayHasKey('default', $data);
                    self::assertArrayHasKey((string)$localization1->getId(), $data);
                    self::assertSame($translation1, $data[(string)$localization1->getId()]);

                    return true;
                })
            );

        $this->innerDataMapper
            ->expects(self::once())
            ->method('mapDataToForms')
            ->with(
                self::identicalTo($emailTemplate),
                self::callback(function ($forms) {
                    return count(iterator_to_array($forms)) === 1 && iterator_to_array($forms)[0] === $this->otherForm;
                })
            );

        $this->dataMapper->mapDataToForms($emailTemplate, $this->forms);
    }

    public function testMapDataToFormsSetsFallbackTranslation(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setSubject('Default subject');
        $emailTemplate->setContent('Default content');

        $localization2 = $this->createLocalization(2);
        $translation2 = $this->createEmailTemplateTranslation($localization2);
        $translation2->setSubjectFallback(true);
        $translation2->setContentFallback(true);
        $translation2->setAttachmentsFallback(true);
        $emailTemplate->addTranslation($translation2);

        $this->emailTemplateTranslationResolver
            ->expects(self::exactly(3))
            ->method('resolveTranslation')
            ->willReturnMap([
                [$emailTemplate, 'subject', $localization2, 'Resolved subject'],
                [$emailTemplate, 'content', $localization2, 'Resolved content'],
                [$emailTemplate, 'attachments', $localization2, new ArrayCollection()],
            ]);

        $defaultForm = $this->createMock(FormInterface::class);
        $translationForm2 = $this->createMock(FormInterface::class);

        $this->translationsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([
                'default' => $defaultForm,
                (string)$localization2->getId() => $translationForm2,
            ]);

        $this->translationsForm
            ->expects(self::once())
            ->method('setData')
            ->with(
                self::callback(static function ($data) use ($emailTemplate, $localization2) {
                    self::assertArrayHasKey('default', $data);
                    self::assertArrayHasKey((string)$localization2->getId(), $data);
                    self::assertSame($emailTemplate, $data[(string)$localization2->getId()]->getTemplate());
                    self::assertEquals('Resolved subject', $data[(string)$localization2->getId()]->getSubject());
                    self::assertEquals('Resolved content', $data[(string)$localization2->getId()]->getContent());

                    return true;
                })
            );

        $this->innerDataMapper
            ->expects(self::once())
            ->method('mapDataToForms')
            ->with(
                self::identicalTo($emailTemplate),
                self::callback(function ($forms) {
                    return count(iterator_to_array($forms)) === 1 && iterator_to_array($forms)[0] === $this->otherForm;
                })
            );

        $this->dataMapper->mapDataToForms($emailTemplate, $this->forms);
    }

    public function testMapDataToFormsSetsDefaultTranslationWithAttachments(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setSubject('Default subject');
        $emailTemplate->setContent('Default content');

        $defaultAttachment = new EmailTemplateAttachment();
        $defaultAttachment->setFilePlaceholder('{{ file.placeholder }}');
        $emailTemplate->addAttachment($defaultAttachment);

        $defaultForm = $this->createMock(FormInterface::class);

        $this->translationsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn(['default' => $defaultForm]);

        $this->translationsForm
            ->expects(self::once())
            ->method('setData')
            ->with(
                self::callback(static function ($data) use ($emailTemplate, $defaultAttachment) {
                    self::assertArrayHasKey('default', $data);
                    self::assertEquals($emailTemplate->getSubject(), $data['default']->getSubject());
                    self::assertEquals($emailTemplate->getContent(), $data['default']->getContent());
                    self::assertSame($emailTemplate, $data['default']->getTemplate());
                    self::assertFalse($data['default']->isSubjectFallback());
                    self::assertFalse($data['default']->isContentFallback());
                    self::assertFalse($data['default']->isAttachmentsFallback());

                    self::assertCount(1, $data['default']->getAttachments());

                    $attachment = $data['default']->getAttachments()->first();
                    self::assertSame($defaultAttachment, $attachment);
                    self::assertNull($attachment->getTranslation());

                    return true;
                })
            );

        $this->innerDataMapper
            ->expects(self::once())
            ->method('mapDataToForms')
            ->with(
                self::identicalTo($emailTemplate),
                self::callback(function ($forms) {
                    return count(iterator_to_array($forms)) === 1 && iterator_to_array($forms)[0] === $this->otherForm;
                })
            );

        $this->dataMapper->mapDataToForms($emailTemplate, $this->forms);
    }

    public function testMapFormsToDataWithNullViewData(): void
    {
        $this->innerDataMapper
            ->expects(self::never())
            ->method('mapFormsToData');

        $viewData = null;
        $this->dataMapper->mapFormsToData($this->forms, $viewData);
    }

    public function testMapFormsToDataWithInvalidViewDataType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $viewData = new \stdClass();
        $this->dataMapper->mapFormsToData($this->forms, $viewData);
    }

    public function testMapFormsToDataUpdatesDefaultTranslation(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setSubject('Original subject');
        $emailTemplate->setContent('Original content');

        $defaultTranslation = new EmailTemplateTranslation();
        $defaultTranslation->setSubject('Updated subject');
        $defaultTranslation->setContent('Updated content');

        $this->translationsForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['default' => $defaultTranslation]);

        $this->innerDataMapper
            ->expects(self::once())
            ->method('mapFormsToData')
            ->with(
                self::callback(function ($forms) {
                    return count(iterator_to_array($forms)) === 1
                        && iterator_to_array($forms)[0] === $this->otherForm;
                }),
                self::identicalTo($emailTemplate)
            );

        $this->dataMapper->mapFormsToData($this->forms, $emailTemplate);

        self::assertEquals('Updated subject', $emailTemplate->getSubject());
        self::assertEquals('Updated content', $emailTemplate->getContent());
    }

    public function testMapFormsToDataRemovesDeletedDefaultAttachments(): void
    {
        $emailTemplate = new EmailTemplate();

        $attachment1 = new EmailTemplateAttachment();
        $attachment1->setFilePlaceholder('{{ file1.placeholder }}');
        $attachment2 = new EmailTemplateAttachment();
        $attachment2->setFilePlaceholder('{{ file2.placeholder }}');

        $emailTemplate->addAttachment($attachment1);
        $emailTemplate->addAttachment($attachment2);

        $defaultTranslation = new EmailTemplateTranslation();
        $defaultTranslation->setSubject('Default subject');
        $defaultTranslation->setContent('Default content');

        $keptAttachment = new EmailTemplateAttachment();
        $keptAttachment->setFilePlaceholder('{{ file1.placeholder }}');
        $defaultTranslation->addAttachment($keptAttachment);

        $this->translationsForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['default' => $defaultTranslation]);

        $this->innerDataMapper
            ->expects(self::once())
            ->method('mapFormsToData')
            ->with(
                self::callback(function ($forms) {
                    return count(iterator_to_array($forms)) === 1
                        && iterator_to_array($forms)[0] === $this->otherForm;
                }),
                self::identicalTo($emailTemplate)
            );

        $this->dataMapper->mapFormsToData($this->forms, $emailTemplate);

        $attachments = $emailTemplate->getAttachments();
        self::assertCount(1, $attachments);
        self::assertEquals('{{ file1.placeholder }}', $attachments->first()->getFilePlaceholder());
    }

    public function testMapFormsToDataSetsFilePlaceholderAndClearsFile(): void
    {
        $emailTemplate = new EmailTemplate();

        $existingAttachment = new EmailTemplateAttachment();
        $file = new File();
        $existingAttachment->setFile($file);
        $emailTemplate->addAttachment($existingAttachment);

        $defaultTranslation = new EmailTemplateTranslation();

        $modifiedAttachment = new EmailTemplateAttachment();
        $modifiedAttachment->setFilePlaceholder('{{ entity.file }}');
        $defaultTranslation->addAttachment($modifiedAttachment);

        $this->translationsForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['default' => $defaultTranslation]);

        $this->dataMapper->mapFormsToData($this->forms, $emailTemplate);

        $attachments = $emailTemplate->getAttachments();
        self::assertCount(1, $attachments);
        self::assertEquals('{{ entity.file }}', $attachments->first()->getFilePlaceholder());
        self::assertSame($emailTemplate, $attachments->first()->getTemplate());
    }

    public function testMapFormsToDataWithTranslationAttachmentsWhenFallbackDisabled(): void
    {
        $emailTemplate = new EmailTemplate();
        $localization = $this->createLocalization(1);

        $existingTranslation = $this->createEmailTemplateTranslation($localization);
        $existingTranslation->setSubjectFallback(false);
        $existingTranslation->setContentFallback(false);
        $existingTranslation->setAttachmentsFallback(true);
        $emailTemplate->addTranslation($existingTranslation);

        $updatedTranslation = $this->createEmailTemplateTranslation($localization);
        $updatedTranslation->setSubjectFallback(false);
        $updatedTranslation->setContentFallback(false);
        $updatedTranslation->setAttachmentsFallback(false);

        $translationAttachment = new EmailTemplateAttachment();
        $translationAttachment->setFilePlaceholder('{{ translation.file }}');
        $updatedTranslation->addAttachment($translationAttachment);

        $this->translationsForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn([
                'default' => null,
                (string)$localization->getId() => $updatedTranslation,
            ]);

        $this->dataMapper->mapFormsToData($this->forms, $emailTemplate);

        $translations = $emailTemplate->getTranslations();
        self::assertCount(1, $translations);
        $translation = $translations->first();
        self::assertFalse($translation->isAttachmentsFallback());

        $attachments = $translation->getAttachments();
        self::assertCount(1, $attachments);
        self::assertEquals('{{ translation.file }}', $attachments->first()->getFilePlaceholder());
        self::assertSame($translation, $attachments->first()->getTranslation());
    }

    public function testMapFormsToDataWithTranslationAttachmentsWhenFallbackEnabled(): void
    {
        $emailTemplate = new EmailTemplate();
        $localization = $this->createLocalization(1);

        $existingTranslation = $this->createEmailTemplateTranslation($localization);
        $existingTranslation->setSubjectFallback(false);
        $existingTranslation->setContentFallback(false);
        $existingTranslation->setAttachmentsFallback(false);

        $translationAttachment = new EmailTemplateAttachment();
        $translationAttachment->setFilePlaceholder('{{ translation.file }}');
        $existingTranslation->addAttachment($translationAttachment);

        $emailTemplate->addTranslation($existingTranslation);

        $updatedTranslation = $this->createEmailTemplateTranslation($localization);
        $updatedTranslation->setSubjectFallback(false);
        $updatedTranslation->setContentFallback(false);
        $updatedTranslation->setAttachmentsFallback(true);

        $this->translationsForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn([
                'default' => null,
                (string)$localization->getId() => $updatedTranslation,
            ]);

        $this->dataMapper->mapFormsToData($this->forms, $emailTemplate);

        $translations = $emailTemplate->getTranslations();
        self::assertCount(1, $translations);
        $translation = $translations->first();
        self::assertTrue($translation->isAttachmentsFallback());
        self::assertCount(0, $translation->getAttachments());
    }

    public function testMapFormsToDataWithMissingTranslationFormData(): void
    {
        $emailTemplate = new EmailTemplate();
        $localization = $this->createLocalization(1);

        $existingTranslation = $this->createEmailTemplateTranslation($localization);
        $existingTranslation->setSubjectFallback(false);
        $existingTranslation->setContentFallback(false);
        $existingTranslation->setAttachmentsFallback(false);
        $existingTranslation->setSubject('Original subject');
        $existingTranslation->setContent('Original content');

        $existingAttachment = new EmailTemplateAttachment();
        $existingAttachment->setFilePlaceholder('{{ translation.file }}');
        $existingTranslation->addAttachment($existingAttachment);

        $emailTemplate->addTranslation($existingTranslation);

        $this->translationsForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn([
                'default' => null,
            ]);

        $this->dataMapper->mapFormsToData($this->forms, $emailTemplate);

        $translations = $emailTemplate->getTranslations();
        self::assertCount(1, $translations);
        $translation = $translations->first();

        self::assertTrue($translation->isSubjectFallback());
        self::assertTrue($translation->isContentFallback());
        self::assertTrue($translation->isAttachmentsFallback());
        self::assertNull($translation->getSubject());
        self::assertNull($translation->getContent());
        self::assertCount(0, $translation->getAttachments());
    }

    public function testMapFormsToDataWithEmptyFileEntity(): void
    {
        $emailTemplate = new EmailTemplate();
        $localization = $this->createLocalization(1);

        $existingTranslation = $this->createEmailTemplateTranslation($localization);
        $existingTranslation->setAttachmentsFallback(false);

        $existingAttachment = new EmailTemplateAttachment();
        $existingFile = new File();
        $existingAttachment->setFile($existingFile);
        $existingTranslation->addAttachment($existingAttachment);

        $emailTemplate->addTranslation($existingTranslation);

        $updatedTranslation = $this->createEmailTemplateTranslation($localization);
        $updatedTranslation->setAttachmentsFallback(false);

        $updatedAttachment = new EmailTemplateAttachment();
        $emptyFile = new File();
        // File has no ID and no file content, but is NOT empty - this triggers cloneFileEntity
        $emptyFile->setOriginalFilename('test.txt');
        $emptyFile->setEmptyFile(false);
        $updatedAttachment->setFile($emptyFile);
        $updatedTranslation->addAttachment($updatedAttachment);

        $clonedFile = new File();
        $this->fileManager
            ->expects(self::once())
            ->method('cloneFileEntity')
            ->with($emptyFile)
            ->willReturn($clonedFile);

        $this->translationsForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn([
                'default' => null,
                (string)$localization->getId() => $updatedTranslation,
            ]);

        $this->dataMapper->mapFormsToData($this->forms, $emailTemplate);

        $translations = $emailTemplate->getTranslations();
        self::assertCount(1, $translations);
        $translation = $translations->first();

        $attachments = $translation->getAttachments();
        self::assertCount(1, $attachments);
        self::assertSame($clonedFile, $attachments->first()->getFile());
        self::assertSame($translation, $attachments->first()->getTranslation());
    }

    public function testMapFormsToDataWithExistingTranslationAttachmentUpdate(): void
    {
        $emailTemplate = new EmailTemplate();
        $localization = $this->createLocalization(1);

        $existingTranslation = $this->createEmailTemplateTranslation($localization);
        $existingTranslation->setAttachmentsFallback(false);

        $existingAttachment = new EmailTemplateAttachment();
        $existingFile = new File();
        ReflectionUtil::setId($existingFile, 1); // Set ID so it doesn't trigger cloneFileEntity
        $existingAttachment->setFile($existingFile);
        $existingTranslation->addAttachment($existingAttachment);

        $emailTemplate->addTranslation($existingTranslation);

        $updatedTranslation = $this->createEmailTemplateTranslation($localization);
        $updatedTranslation->setAttachmentsFallback(false);

        $updatedAttachment = new EmailTemplateAttachment();
        $updatedFile = new File();
        ReflectionUtil::setId($updatedFile, 2); // Set ID so it doesn't trigger cloneFileEntity
        $updatedAttachment->setFile($updatedFile);
        $updatedTranslation->addAttachment($updatedAttachment);

        $this->fileManager
            ->expects(self::never()) // No clone should happen for files with IDs
            ->method('cloneFileEntity');

        $this->translationsForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn([
                'default' => null,
                (string)$localization->getId() => $updatedTranslation,
            ]);

        $this->dataMapper->mapFormsToData($this->forms, $emailTemplate);

        $translations = $emailTemplate->getTranslations();
        self::assertCount(1, $translations);
        $translation = $translations->first();

        $attachments = $translation->getAttachments();
        self::assertCount(1, $attachments);
        self::assertSame($updatedFile, $attachments->first()->getFile());
        self::assertSame($translation, $attachments->first()->getTranslation());
    }

    public function testMapFormsToDataWithTrulyEmptyFileEntity(): void
    {
        $emailTemplate = new EmailTemplate();
        $localization = $this->createLocalization(1);

        $existingTranslation = $this->createEmailTemplateTranslation($localization);
        $existingTranslation->setAttachmentsFallback(false);

        $existingAttachment = new EmailTemplateAttachment();
        $existingFile = new File();
        $existingAttachment->setFile($existingFile);
        $existingTranslation->addAttachment($existingAttachment);

        $emailTemplate->addTranslation($existingTranslation);

        $updatedTranslation = $this->createEmailTemplateTranslation($localization);
        $updatedTranslation->setAttachmentsFallback(false);

        $updatedAttachment = new EmailTemplateAttachment();
        $emptyFile = new File();
        // File has no ID, no file content, and IS empty - this sets file to null
        $emptyFile->setEmptyFile(true);
        $updatedAttachment->setFile($emptyFile);
        $updatedTranslation->addAttachment($updatedAttachment);

        $this->fileManager
            ->expects(self::never()) // No clone should happen for files going to be deleted
            ->method('cloneFileEntity');

        $this->translationsForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn([
                'default' => null,
                (string)$localization->getId() => $updatedTranslation,
            ]);

        $this->dataMapper->mapFormsToData($this->forms, $emailTemplate);

        $translations = $emailTemplate->getTranslations();
        self::assertCount(1, $translations);
        $translation = $translations->first();

        $attachments = $translation->getAttachments();
        self::assertCount(1, $attachments);
        self::assertNull($attachments->first()->getFile()); // File should be null for empty files
        self::assertSame($translation, $attachments->first()->getTranslation());
    }

    public function testMapFormsToDataWithFileEntityHavingFileContent(): void
    {
        $emailTemplate = new EmailTemplate();
        $localization = $this->createLocalization(1);

        $existingTranslation = $this->createEmailTemplateTranslation($localization);
        $existingTranslation->setAttachmentsFallback(false);

        $existingAttachment = new EmailTemplateAttachment();
        $existingFile = new File();
        $existingAttachment->setFile($existingFile);
        $existingTranslation->addAttachment($existingAttachment);

        $emailTemplate->addTranslation($existingTranslation);

        $updatedTranslation = $this->createEmailTemplateTranslation($localization);
        $updatedTranslation->setAttachmentsFallback(false);

        $updatedAttachment = new EmailTemplateAttachment();
        $fileWithContent = new File();
        // File has no ID but has file content - no clone needed
        $fileWithContent->setFile($this->createMock(\SplFileInfo::class));
        $updatedAttachment->setFile($fileWithContent);
        $updatedTranslation->addAttachment($updatedAttachment);

        $this->fileManager
            ->expects(self::never()) // No clone should happen for files with content
            ->method('cloneFileEntity');

        $this->translationsForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn([
                'default' => null,
                (string)$localization->getId() => $updatedTranslation,
            ]);

        $this->dataMapper->mapFormsToData($this->forms, $emailTemplate);

        $translations = $emailTemplate->getTranslations();
        self::assertCount(1, $translations);
        $translation = $translations->first();

        $attachments = $translation->getAttachments();
        self::assertCount(1, $attachments);
        self::assertSame($fileWithContent, $attachments->first()->getFile());
        self::assertSame($translation, $attachments->first()->getTranslation());
    }
}
