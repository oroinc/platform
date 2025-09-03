<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Type;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateAttachmentType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateEntityChoiceType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationCollectionType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailTemplateTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([
            LoadOrganization::class,
            LoadUser::class,
        ]);
    }

    public function testHasFields(): void
    {
        $form = self::createForm(EmailTemplateType::class);

        self::assertFormOptions($form, [
            'data_class' => EmailTemplate::class,
            'csrf_token_id' => 'emailtemplate',
        ]);

        self::assertFormHasField($form, 'name', TextType::class, [
            'required' => true,
            'label' => 'oro.email.emailtemplate.name.label',
        ]);

        self::assertFormHasField($form, 'type', ChoiceType::class, [
            'required' => true,
            'expanded' => true,
            'multiple' => false,
            'choices' => [
                'oro.email.datagrid.emailtemplate.filter.type.html' => 'html',
                'oro.email.datagrid.emailtemplate.filter.type.txt' => 'txt',
            ],
            'label' => 'oro.email.emailtemplate.type.label',
        ]);

        self::assertFormHasField($form, 'entityName', EmailTemplateEntityChoiceType::class, [
            'required' => false,
            'label' => 'oro.email.emailtemplate.entity_name.label',
            'tooltip' => 'oro.email.emailtemplate.entity_name.tooltip',
            'configs' => ['allowClear' => true],
        ]);

        $localizations = self::getContainer()->get('oro_locale.manager.localization')->getLocalizations();
        $wysiwygEnabled = self::getContainer()->get('oro_config.user')
            ->get('oro_email.email_template_wysiwyg_enabled') ?? false;

        self::assertFormHasField($form, 'translations', EmailTemplateTranslationCollectionType::class, [
            'localizations' => $localizations,
            'wysiwyg_enabled' => $wysiwygEnabled,
            'wysiwyg_options' => [
                'convert_urls' => false,
            ],
        ]);

        self::assertFormHasField($form, 'activeLocalization', HiddenType::class, [
            'mapped' => false,
            'attr' => ['class' => 'active-localization'],
        ]);

        self::assertFormHasField($form, 'parentTemplate', HiddenType::class, [
            'label' => 'oro.email.emailtemplate.parent.label',
            'property_path' => 'parent',
        ]);
    }

    public function testFieldsForNonSystemEmailTemplate(): void
    {
        // Create non-system email template
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('user_template');
        $emailTemplate->setIsSystem(false);
        $emailTemplate->setEntityName('Oro\Bundle\UserBundle\Entity\User');

        // Set an ID to simulate that it's a persisted entity
        ReflectionUtil::setId($emailTemplate, 1);

        $form = self::createForm(EmailTemplateType::class, $emailTemplate);

        // All fields should be enabled for non-system templates
        self::assertFalse(
            $form->get('name')->getConfig()->getOption('disabled'),
            'Name field should be enabled for non-system templates'
        );

        self::assertFalse(
            $form->get('entityName')->getConfig()->getOption('disabled'),
            'EntityName field should be enabled for non-system templates'
        );

        self::assertFalse(
            $form->get('type')->getConfig()->getOption('disabled'),
            'Type field should be enabled for non-system templates'
        );
    }

    public function testNameAndEntityNameFieldsDisabledForSystemTemplate(): void
    {
        // Create a system email template
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('system_template');
        $emailTemplate->setIsSystem(true);
        $emailTemplate->setEntityName('Oro\Bundle\UserBundle\Entity\User');

        // Set an ID to simulate that it's a persisted entity
        ReflectionUtil::setId($emailTemplate, 1);

        $form = self::createForm(EmailTemplateType::class, $emailTemplate);

        // For system templates, name and entityName should be disabled, but type should be enabled
        self::assertTrue(
            $form->get('name')->getConfig()->getOption('disabled'),
            'Name field should be disabled for system templates'
        );

        self::assertTrue(
            $form->get('entityName')->getConfig()->getOption('disabled'),
            'EntityName field should be disabled for system templates'
        );

        self::assertFalse(
            $form->get('type')->getConfig()->getOption('disabled'),
            'Type field should not be disabled for editable system templates'
        );
    }

    public function testAllFieldsDisabledForNonEditableSystemTemplate(): void
    {
        // Create a non-editable system email template
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('system_template_non_editable');
        $emailTemplate->setIsSystem(true);
        $emailTemplate->setIsEditable(false);
        $emailTemplate->setEntityName('Oro\Bundle\UserBundle\Entity\User');

        // Set an ID to simulate that it's a persisted entity
        ReflectionUtil::setId($emailTemplate, 1);

        $form = self::createForm(EmailTemplateType::class, $emailTemplate);

        // All fields should be disabled for non-editable system templates
        self::assertTrue(
            $form->get('name')->getConfig()->getOption('disabled'),
            'Name field should be disabled for non-editable system templates'
        );

        self::assertTrue(
            $form->get('entityName')->getConfig()->getOption('disabled'),
            'EntityName field should be disabled for non-editable system templates'
        );

        self::assertTrue(
            $form->get('type')->getConfig()->getOption('disabled'),
            'Type field should be disabled for non-editable system templates'
        );
    }

    public function testSystemButNoIdTemplateFieldsNotDisabled(): void
    {
        // Create a system email template without an ID (not yet persisted)
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('new_system_template');
        $emailTemplate->setIsSystem(true);
        $emailTemplate->setEntityName('Oro\Bundle\UserBundle\Entity\User');

        $form = self::createForm(EmailTemplateType::class, $emailTemplate);

        // Fields should not be disabled for system templates without an ID
        self::assertFalse(
            $form->get('name')->getConfig()->getOption('disabled'),
            'Name field should not be disabled for system templates without an ID'
        );

        self::assertFalse(
            $form->get('entityName')->getConfig()->getOption('disabled'),
            'EntityName field should not be disabled for system templates without an ID'
        );

        self::assertFalse(
            $form->get('type')->getConfig()->getOption('disabled'),
            'Type field should not be disabled for system templates without an ID'
        );
    }

    public function testHasAttachmentChoices(): void
    {
        // Create an email template with an entity class that has attachment fields
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('template_with_attachments');
        $emailTemplate->setType('html');
        $emailTemplate->setEntityName(User::class);
        $emailTemplate->addAttachment((new EmailTemplateAttachment())->setFilePlaceholder('entity.avatar'));

        // Create a form with this template as data to trigger entity class processing
        $form = self::createForm(EmailTemplateType::class, $emailTemplate);

        // Get the translations form to inspect its configuration
        $translationsForm = $form->get('translations');

        // Check that the entity_class option was properly set
        self::assertEquals(
            User::class,
            $translationsForm->getConfig()->getOption('entity_class')
        );

        // Get the default translation form to check attachment choices
        $defaultTranslationForm = $translationsForm->get('default');
        self::assertTrue($defaultTranslationForm->has('attachments'));

        $attachmentsForm = $defaultTranslationForm->get('attachments');
        self::assertNotEmpty($attachmentsForm);

        // Check that the attachments field has choices based on the entity class
        $attachmentChoices = $attachmentsForm->get('0')->get('filePlaceholder')->getConfig()->getOption('choices');
        self::assertEquals([
            'Avatar' => 'entity.avatar',
            'oro.email.emailtemplateattachment.file_placeholder.choices.upload_file_group' => [
                'oro.email.emailtemplateattachment.file_placeholder.choices.upload_file' =>
                    EmailTemplateAttachmentType::UPLOAD_FILE,
            ],
        ], $attachmentChoices);
    }

    public function testDataIsMappedToFormWhenNoTranslations(): void
    {
        // Create a simple email template without translations
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('test_template');
        $emailTemplate->setType('html');
        $emailTemplate->setSubject('Test Subject');
        $emailTemplate->setContent('Test Content <b>Bold</b>');
        $emailTemplate->setEntityName('Oro\Bundle\UserBundle\Entity\User');

        // Create the form with the template as data
        $form = self::createForm(EmailTemplateType::class, $emailTemplate);

        // Check that the name field is correctly mapped
        self::assertEquals('test_template', $form->get('name')->getData());

        // Check that the type field is correctly mapped
        self::assertEquals('html', $form->get('type')->getData());

        // Check that the entity name field is correctly mapped
        self::assertEquals('Oro\Bundle\UserBundle\Entity\User', $form->get('entityName')->getData());

        // Check that translations collection contains the default entry with correct data
        $translationsData = $form->get('translations')->getData();
        self::assertArrayHasKey('default', $translationsData);
        self::assertEquals('Test Subject', $translationsData['default']->getSubject());
        self::assertEquals('Test Content <b>Bold</b>', $translationsData['default']->getContent());

        // Check that other localizations are represented in the form but without content
        $localizationIds = array_map(
            static fn ($localization) => $localization->getId(),
            self::getContainer()->get('oro_locale.manager.localization')->getLocalizations()
        );

        foreach ($localizationIds as $localizationId) {
            self::assertArrayHasKey($localizationId, $translationsData);
            // For new forms with no translations, each localization should have fallback enabled
            self::assertTrue($translationsData[$localizationId]->isSubjectFallback());
            self::assertTrue($translationsData[$localizationId]->isContentFallback());
            self::assertTrue($translationsData[$localizationId]->isAttachmentsFallback());
        }
    }

    public function testDataIsMappedToFormWhenHasTranslations(): void
    {
        // Create a simple email template
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('test_template');
        $emailTemplate->setType('html');
        $emailTemplate->setSubject('Default Subject');
        $emailTemplate->setContent('Default Content <b>Bold</b>');
        $emailTemplate->setEntityName('Oro\Bundle\UserBundle\Entity\User');

        // Get localizations to set up translations
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');
        $localizations = $localizationManager->getLocalizations();

        // If there are no localizations in the test environment, we can't really test translations
        if (count($localizations) === 0) {
            self::markTestSkipped('No localizations available for testing');
        }

        $localization = reset($localizations);

        // Add a translation for the first localization
        $translation = new EmailTemplateTranslation();
        $translation->setLocalization($localization);
        $translation->setSubject('Translated Subject');
        $translation->setContent('Translated Content <i>Italic</i>');
        $translation->setSubjectFallback(false);
        $translation->setContentFallback(false);
        $translation->setAttachmentsFallback(false);

        $emailTemplate->addTranslation($translation);

        // Create the form with the template as data
        $form = self::createForm(EmailTemplateType::class, $emailTemplate);

        // Check that the base fields are correctly mapped
        self::assertEquals('test_template', $form->get('name')->getData());
        self::assertEquals('html', $form->get('type')->getData());
        self::assertEquals('Oro\Bundle\UserBundle\Entity\User', $form->get('entityName')->getData());

        // Check translations collection
        $translationsData = $form->get('translations')->getData();

        // Check default translation
        self::assertArrayHasKey('default', $translationsData);
        self::assertEquals('Default Subject', $translationsData['default']->getSubject());
        self::assertEquals('Default Content <b>Bold</b>', $translationsData['default']->getContent());

        // Check localized translation
        $localizationId = $localization->getId();
        self::assertArrayHasKey($localizationId, $translationsData);
        self::assertEquals('Translated Subject', $translationsData[$localizationId]->getSubject());
        self::assertEquals('Translated Content <i>Italic</i>', $translationsData[$localizationId]->getContent());
        self::assertFalse($translationsData[$localizationId]->isSubjectFallback());
        self::assertFalse($translationsData[$localizationId]->isContentFallback());
        self::assertFalse($translationsData[$localizationId]->isAttachmentsFallback());

        // Check that other localizations are represented in the form but with fallback enabled
        foreach ($localizations as $otherLocalization) {
            if ($otherLocalization->getId() === $localizationId) {
                continue;
            }

            $otherLocalizationId = $otherLocalization->getId();
            self::assertArrayHasKey($otherLocalizationId, $translationsData);
            self::assertTrue($translationsData[$otherLocalizationId]->isSubjectFallback());
            self::assertTrue($translationsData[$otherLocalizationId]->isContentFallback());
            self::assertTrue($translationsData[$otherLocalizationId]->isAttachmentsFallback());

            // When fallback is enabled, it should show the default content
            self::assertEquals('Default Subject', $translationsData[$otherLocalizationId]->getSubject());
            self::assertEquals('Default Content <b>Bold</b>', $translationsData[$otherLocalizationId]->getContent());
        }
    }

    public function testDataIsMappedToFormWhenHasAttachments(): void
    {
        // Create a simple email template
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('test_template_with_attachments');
        $emailTemplate->setType('html');
        $emailTemplate->setSubject('Template with Attachments');
        $emailTemplate->setContent('Email content with attachment references');
        $emailTemplate->setEntityName('Oro\Bundle\UserBundle\Entity\User');

        // Add attachments
        $attachment1 = new EmailTemplateAttachment();
        $attachment1->setFilePlaceholder('{{ entity.pdfFile }}');

        $emailTemplate->addAttachment($attachment1);

        // Create the form with the template as data
        $form = self::createForm(EmailTemplateType::class, $emailTemplate);

        // Verify basic fields are mapped correctly
        self::assertEquals('test_template_with_attachments', $form->get('name')->getData());
        self::assertEquals('html', $form->get('type')->getData());

        // Check that the default translation contains the attachments
        $translationsData = $form->get('translations')->getData();
        self::assertArrayHasKey('default', $translationsData);

        $defaultTranslationAttachments = $translationsData['default']->getAttachments();
        self::assertCount(1, $defaultTranslationAttachments);

        // Verify attachment data is correctly mapped
        $attachmentsArray = $defaultTranslationAttachments->toArray();

        self::assertEquals('{{ entity.pdfFile }}', $attachmentsArray[0]->getFilePlaceholder());

        // Check that no attachments were lost during mapping
        self::assertCount(1, $emailTemplate->getAttachments());
    }

    public function testDataIsMappedToFormWhenHasAttachmentsInTranslations(): void
    {
        // Create a simple email template
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('test_template_with_localized_attachments');
        $emailTemplate->setType('html');
        $emailTemplate->setSubject('Template with Localized Attachments');
        $emailTemplate->setContent('Email content in default language');
        $emailTemplate->setEntityName('Oro\Bundle\UserBundle\Entity\User');

        // Get localizations to set up translations
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');
        $localizations = $localizationManager->getLocalizations();

        if (count($localizations) === 0) {
            self::markTestSkipped('No localizations available for testing');
        }

        $localization = reset($localizations);

        // Create translation with its own attachments
        $translation = new EmailTemplateTranslation();
        $translation->setLocalization($localization);
        $translation->setSubject('Localized Subject');
        $translation->setContent('Localized Content');
        $translation->setSubjectFallback(false);
        $translation->setContentFallback(false);
        $translation->setAttachmentsFallback(false);

        // Add localized attachment
        $localizedAttachment = new EmailTemplateAttachment();
        $localizedAttachment->setFilePlaceholder('{{ entity.localizedFile }}');
        $translation->addAttachment($localizedAttachment);

        $emailTemplate->addTranslation($translation);

        // Add default attachment
        $defaultAttachment = new EmailTemplateAttachment();
        $defaultAttachment->setFilePlaceholder('{{ entity.defaultFile }}');
        $emailTemplate->addAttachment($defaultAttachment);

        // Create the form with the template as data
        $form = self::createForm(EmailTemplateType::class, $emailTemplate);

        // Verify base fields are mapped correctly
        self::assertEquals('test_template_with_localized_attachments', $form->get('name')->getData());
        self::assertEquals('html', $form->get('type')->getData());

        // Check translations collection
        $translationsData = $form->get('translations')->getData();

        // Check default translation attachments
        self::assertArrayHasKey('default', $translationsData);
        $defaultTranslationAttachments = $translationsData['default']->getAttachments();
        self::assertCount(1, $defaultTranslationAttachments);

        $defaultAttachmentsArray = $defaultTranslationAttachments->toArray();
        self::assertEquals('{{ entity.defaultFile }}', $defaultAttachmentsArray[0]->getFilePlaceholder());

        // Check localized translation attachments
        $localizationId = $localization->getId();
        self::assertArrayHasKey($localizationId, $translationsData);

        $localizedTranslationAttachments = $translationsData[$localizationId]->getAttachments();
        self::assertCount(1, $localizedTranslationAttachments);
        self::assertFalse($translationsData[$localizationId]->isAttachmentsFallback());

        $localizedAttachmentsArray = $localizedTranslationAttachments->toArray();
        self::assertEquals('{{ entity.localizedFile }}', $localizedAttachmentsArray[0]->getFilePlaceholder());

        // Check that for other localizations, attachments fallback to default
        foreach ($localizations as $otherLocalization) {
            if ($otherLocalization->getId() === $localizationId) {
                continue;
            }

            $otherLocalizationId = $otherLocalization->getId();
            self::assertArrayHasKey($otherLocalizationId, $translationsData);
            self::assertTrue($translationsData[$otherLocalizationId]->isAttachmentsFallback());

            $otherLocalizationAttachments = $translationsData[$otherLocalizationId]->getAttachments();
            self::assertCount(1, $otherLocalizationAttachments);

            $otherAttachmentsArray = $otherLocalizationAttachments->toArray();
            self::assertEquals('{{ entity.defaultFile }}', $otherAttachmentsArray[0]->getFilePlaceholder());
        }
    }

    public function testSubmitWithoutTranslations(): void
    {
        // Create a form without pre-existing data
        $form = self::createForm(EmailTemplateType::class);

        // Get a reference to the user for the owner field
        $user = $this->getReference(LoadUser::USER);

        // Get localizations to set up translations
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');
        $localizations = $localizationManager->getLocalizations();

        // If there are no localizations in the test environment, we can't really test translations
        if (count($localizations) === 0) {
            self::markTestSkipped('No localizations available for testing');
        }

        $localization = reset($localizations);
        $localizationId = $localization->getId();

        $formData = [
            'name' => 'test_template',
            'type' => 'html',
            'entityName' => 'Oro\Bundle\UserBundle\Entity\User',
            'translations' => [
                'default' => [
                    'subject' => 'Test Subject',
                    'content' => 'Test Content <b>Bold</b>',
                    'subjectFallback' => false,
                    'contentFallback' => false,
                    'attachmentsFallback' => false,
                ],
                $localizationId => [
                    'subject' => '',
                    'content' => '',
                    'subjectFallback' => true,
                    'contentFallback' => true,
                    'attachmentsFallback' => true,
                ],
            ],
            'owner' => $user->getId(),
        ];

        // Submit the form
        $form->submit($formData);

        // Check form is valid and synchronized
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string)$form->getErrors(true, false));

        /** @var EmailTemplate $data */
        $data = $form->getData();

        // Verify that the basic template properties are set
        self::assertInstanceOf(EmailTemplate::class, $data);
        self::assertEquals('test_template', $data->getName());
        self::assertEquals('html', $data->getType());
        self::assertEquals('Oro\Bundle\UserBundle\Entity\User', $data->getEntityName());
        self::assertEquals('Test Subject', $data->getSubject());
        self::assertEquals('Test Content <b>Bold</b>', $data->getContent());
        self::assertSame($user, $data->getOwner());
        self::assertSame($user->getOrganization(), $data->getOrganization());

        // Check that translations collection has 1 translation with fallbacks
        self::assertCount(1, $data->getTranslations());

        // Get the first translation
        $translation = $data->getTranslations()->first();
        self::assertInstanceOf(EmailTemplateTranslation::class, $translation);

        // Since we submitted data for localization ID 1, check if it was properly set
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');
        $localization = $localizationManager->getLocalization(1);
        self::assertSame($localization, $translation->getLocalization());

        // Verify fallback flags are set correctly - should be true as submitted
        self::assertTrue($translation->isSubjectFallback());
        self::assertTrue($translation->isContentFallback());
        self::assertTrue($translation->isAttachmentsFallback());

        // Check that attachments collection is empty
        self::assertCount(0, $data->getAttachments());
    }

    public function testSubmitWithTranslations(): void
    {
        // Get a reference to the user for the owner field
        $user = $this->getReference(LoadUser::USER);

        // Get localizations to set up translations
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');
        $localizations = $localizationManager->getLocalizations();

        // If there are no localizations in the test environment, we can't really test translations
        if (count($localizations) === 0) {
            self::markTestSkipped('No localizations available for testing');
        }

        $localization = reset($localizations);
        $localizationId = $localization->getId();

        // Create form with empty data
        $form = self::createForm(EmailTemplateType::class);

        $formData = [
            'name' => 'test_template_with_translations',
            'type' => 'html',
            'entityName' => 'Oro\Bundle\UserBundle\Entity\User',
            'translations' => [
                'default' => [
                    'subject' => 'Default Subject',
                    'content' => 'Default Content <strong>Bold</strong>',
                    'subjectFallback' => false,
                    'contentFallback' => false,
                    'attachmentsFallback' => false,
                ],
                $localizationId => [
                    'subject' => 'Translated Subject',
                    'content' => 'Translated Content <em>Emphasized</em>',
                    'subjectFallback' => false,
                    'contentFallback' => false,
                    'attachmentsFallback' => false,
                ],
            ],
            'owner' => $user->getId(),
        ];

        // Submit the form
        $form->submit($formData);

        // Check form is valid and synchronized
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string)$form->getErrors(true, false));

        /** @var EmailTemplate $data */
        $data = $form->getData();

        // Verify that the basic template properties are set
        self::assertInstanceOf(EmailTemplate::class, $data);
        self::assertEquals('test_template_with_translations', $data->getName());
        self::assertEquals('html', $data->getType());
        self::assertEquals('Oro\Bundle\UserBundle\Entity\User', $data->getEntityName());
        self::assertEquals('Default Subject', $data->getSubject());
        self::assertEquals('Default Content <strong>Bold</strong>', $data->getContent());

        // Check that translations collection has the expected translation
        self::assertCount(1, $data->getTranslations());

        // Get the translation
        $translation = $data->getTranslations()->first();
        self::assertInstanceOf(EmailTemplateTranslation::class, $translation);

        // Verify the translation has the right localization
        self::assertSame($localization, $translation->getLocalization());

        // Verify the translation content
        self::assertEquals('Translated Subject', $translation->getSubject());
        self::assertEquals('Translated Content <em>Emphasized</em>', $translation->getContent());
        self::assertFalse($translation->isSubjectFallback());
        self::assertFalse($translation->isContentFallback());
        self::assertFalse($translation->isAttachmentsFallback());
    }

    public function testSubmitWithAttachments(): void
    {
        // Get a reference to the user for the owner field
        $user = $this->getReference(LoadUser::USER);

        // Create form with empty data
        $form = self::createForm(EmailTemplateType::class);

        // Get localizations to set up translations
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');
        $localizations = $localizationManager->getLocalizations();

        // If there are no localizations in the test environment, we can't really test translations
        if (count($localizations) === 0) {
            self::markTestSkipped('No localizations available for testing');
        }

        $localization = reset($localizations);
        $localizationId = $localization->getId();

        $formData = [
            'name' => 'test_template_with_attachments',
            'type' => 'html',
            'entityName' => 'Oro\Bundle\UserBundle\Entity\User',
            'translations' => [
                'default' => [
                    'subject' => 'Template with Attachments',
                    'content' => 'Email content with attachment references',
                    'subjectFallback' => false,
                    'contentFallback' => false,
                    'attachmentsFallback' => false,
                    'attachments' => [
                        [
                            'filePlaceholder' => 'entity.avatar',
                        ],
                    ],
                ],
                $localizationId => [
                    'subject' => '',
                    'content' => '',
                    'subjectFallback' => true,
                    'contentFallback' => true,
                    'attachmentsFallback' => true,
                ],
            ],
            'owner' => $user->getId(),
        ];

        // Submit the form
        $form->submit($formData);

        // Check form is valid and synchronized
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string)$form->getErrors(true, false));

        /** @var EmailTemplate $data */
        $data = $form->getData();

        // Verify that the basic template properties are set
        self::assertInstanceOf(EmailTemplate::class, $data);
        self::assertEquals('test_template_with_attachments', $data->getName());
        self::assertEquals('html', $data->getType());
        self::assertEquals('Template with Attachments', $data->getSubject());
        self::assertEquals('Email content with attachment references', $data->getContent());

        // Verify that attachments were added
        self::assertCount(1, $data->getAttachments());

        $attachmentsArray = $data->getAttachments()->toArray();

        self::assertEquals('entity.avatar', $attachmentsArray[0]->getFilePlaceholder());
        self::assertSame($data, $attachmentsArray[0]->getTemplate());
        self::assertNull($attachmentsArray[0]->getTranslation());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSubmitWithAttachmentsInTranslations(): void
    {
        // Get a reference to the user for the owner field
        $user = $this->getReference(LoadUser::USER);

        // Get localizations to set up translations
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');
        $localizations = $localizationManager->getLocalizations();

        // If there are no localizations in the test environment, we can't really test translations
        if (count($localizations) === 0) {
            self::markTestSkipped('No localizations available for testing');
        }

        $localization = reset($localizations);
        $localizationId = $localization->getId();

        // Create form with empty data
        $form = self::createForm(EmailTemplateType::class);

        $formData = [
            'name' => 'test_template_with_localized_attachments',
            'type' => 'html',
            'entityName' => 'Oro\Bundle\UserBundle\Entity\User',
            'translations' => [
                'default' => [
                    'subject' => 'Template with Localized Attachments',
                    'content' => 'Email content in default language',
                    'subjectFallback' => false,
                    'contentFallback' => false,
                    'attachmentsFallback' => false,
                    'attachments' => [
                        [
                            'filePlaceholder' => 'entity.avatar',
                        ],
                    ],
                ],
                $localizationId => [
                    'subject' => 'Localized Template Subject',
                    'content' => 'Localized Email Content',
                    'subjectFallback' => false,
                    'contentFallback' => false,
                    'attachmentsFallback' => false,
                    'attachments' => [
                        [
                            'filePlaceholder' => 'entity.avatar',
                        ],
                    ],
                ],
            ],
            'owner' => $user->getId(),
        ];

        // Submit the form
        $form->submit($formData);

        // Check form is valid and synchronized
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string)$form->getErrors(true, false));

        /** @var EmailTemplate $data */
        $data = $form->getData();

        // Verify that the basic template properties are set
        self::assertInstanceOf(EmailTemplate::class, $data);
        self::assertEquals('test_template_with_localized_attachments', $data->getName());
        self::assertEquals('html', $data->getType());
        self::assertEquals('Template with Localized Attachments', $data->getSubject());
        self::assertEquals('Email content in default language', $data->getContent());

        // Verify default attachments
        self::assertCount(1, $data->getAttachments());
        $defaultAttachment = $data->getAttachments()->first();
        self::assertEquals('entity.avatar', $defaultAttachment->getFilePlaceholder());
        self::assertSame($data, $defaultAttachment->getTemplate());
        self::assertNull($defaultAttachment->getTranslation());

        // Verify that localized translation was created with attachments
        self::assertCount(1, $data->getTranslations());

        $translation = $data->getTranslations()->first();
        self::assertInstanceOf(EmailTemplateTranslation::class, $translation);
        self::assertSame($localization, $translation->getLocalization());

        self::assertEquals('Localized Template Subject', $translation->getSubject());
        self::assertEquals('Localized Email Content', $translation->getContent());
        self::assertFalse($translation->isSubjectFallback());
        self::assertFalse($translation->isContentFallback());
        self::assertFalse($translation->isAttachmentsFallback());

        // Verify translation attachments
        self::assertCount(1, $translation->getAttachments());
        $translationAttachments = $translation->getAttachments()->toArray();

        self::assertEquals('entity.avatar', $translationAttachments[0]->getFilePlaceholder());
        self::assertNull($translationAttachments[0]->getTemplate());
        self::assertSame($translation, $translationAttachments[0]->getTranslation());
    }
}
