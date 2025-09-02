<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Type;

use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateAttachmentCollectionType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateAttachmentType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateRichTextType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationType;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailTemplateTranslationTypeTest extends WebTestCase
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
            LoadLocalizationData::class,
        ]);
    }

    public function testHasFieldsWhenNoLocalization(): void
    {
        $form = self::createForm(EmailTemplateTranslationType::class, null, [
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
        ]);

        self::assertFormOptions($form, [
            'data_class' => EmailTemplateTranslation::class,
            'allow_extra_fields' => true,
            'localization' => null,
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
        ]);

        self::assertFormHasField($form, 'subject', TextType::class, [
            'attr' => [
                'maxlength' => 255,
            ],
            'required' => false,
        ]);

        self::assertFormHasField($form, 'content', EmailTemplateRichTextType::class, [
            'required' => false,
            'wysiwyg_enabled' => false,
        ]);

        self::assertFormHasField($form, 'attachments', EmailTemplateAttachmentCollectionType::class, [
            'entry_type' => EmailTemplateAttachmentType::class,
            'entry_options' => [
                'label' => false,
                'required' => false,
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
        ]);
    }

    public function testHasFieldsWhenSetLocalization(): void
    {
        $translator = self::getContainer()->get('translator');
        $localization = $this->getReference(LoadLocalizationData::DEFAULT_LOCALIZATION_CODE);

        $form = self::createForm(EmailTemplateTranslationType::class, null, [
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => [],
            'localization' => $localization,
        ]);

        self::assertFormOptions($form, [
            'data_class' => EmailTemplateTranslation::class,
            'allow_extra_fields' => true,
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => [],
            'localization' => $localization,
        ]);

        // Check form fields with all options
        self::assertFormHasField($form, 'subject', TextType::class, [
            'attr' => [
                'maxlength' => 255,
            ],
            'required' => false,
        ]);

        $fallbackLabel = $translator->trans('oro.email.emailtemplatetranslation.form.use_default_localization');

        self::assertFormHasField($form, 'subjectFallback', CheckboxType::class, [
            'required' => false,
            'label' => $fallbackLabel,
        ]);

        self::assertFormHasField($form, 'content', EmailTemplateRichTextType::class, [
            'required' => false,
            'wysiwyg_enabled' => true,
        ]);

        self::assertFormHasField($form, 'contentFallback', CheckboxType::class, [
            'required' => false,
            'label' => $fallbackLabel,
        ]);

        self::assertFormHasField($form, 'attachmentsFallback', CheckboxType::class, [
            'required' => false,
            'label' => $fallbackLabel,
        ]);

        self::assertFormHasField($form, 'attachments', EmailTemplateAttachmentCollectionType::class, [
            'entry_type' => EmailTemplateAttachmentType::class,
            'entry_options' => [
                'label' => false,
                'required' => false,
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
        ]);
    }

    public function testHasFieldsWhenSetLocalizationWithParent(): void
    {
        $translator = self::getContainer()->get('translator');
        $localizationManager = self::getContainer()->get('oro_locale.manager.localization');
        $localizationWithParent = $this->getReference(LoadLocalizationData::EN_CA_LOCALIZATION_CODE);

        // Create form with the localization that has a parent
        $form = self::createForm(EmailTemplateTranslationType::class, null, [
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => [],
            'localization' => $localizationWithParent,
        ]);

        self::assertFormOptions($form, [
            'data_class' => EmailTemplateTranslation::class,
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => [],
            'localization' => $localizationWithParent,
        ]);

        // Get parent localization name for fallback label
        $parentName = $localizationWithParent->getParentLocalization()->getTitle(
            $localizationManager->getDefaultLocalization()
        );

        // Get the expected fallback label that should reference the parent localization
        $fallbackLabel = $translator->trans(
            'oro.email.emailtemplatetranslation.form.use_parent_localization',
            ['%name%' => $parentName]
        );

        // Check form fields with all options
        self::assertFormHasField($form, 'subject', TextType::class, [
            'attr' => [
                'maxlength' => 255,
            ],
            'required' => false,
        ]);

        self::assertFormHasField($form, 'subjectFallback', CheckboxType::class, [
            'required' => false,
            'label' => $fallbackLabel,
        ]);

        self::assertFormHasField($form, 'content', EmailTemplateRichTextType::class, [
            'required' => false,
            'wysiwyg_enabled' => true,
        ]);

        self::assertFormHasField($form, 'contentFallback', CheckboxType::class, [
            'required' => false,
            'label' => $fallbackLabel,
        ]);

        self::assertFormHasField($form, 'attachmentsFallback', CheckboxType::class, [
            'required' => false,
            'label' => $fallbackLabel,
        ]);

        self::assertFormHasField($form, 'attachments', EmailTemplateAttachmentCollectionType::class, [
            'entry_type' => EmailTemplateAttachmentType::class,
            'entry_options' => [
                'label' => false,
                'required' => false,
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
        ]);
    }

    public function testHasFieldsWhenHasEntityClass(): void
    {
        // Create form with an entity class that has attachment fields
        $form = self::createForm(EmailTemplateTranslationType::class, null, [
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
            'entity_class' => User::class,
        ]);

        self::assertFormOptions($form, [
            'data_class' => EmailTemplateTranslation::class,
            'allow_extra_fields' => true,
            'localization' => null,
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
            'entity_class' => User::class,
        ]);

        // Verify that the entity_class option is passed to the attachments field
        self::assertFormHasField($form, 'attachments', EmailTemplateAttachmentCollectionType::class, [
            'entry_type' => EmailTemplateAttachmentType::class,
            'entry_options' => [
                'label' => false,
                'required' => false,
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'entity_class' => User::class,
        ]);
    }

    public function testAttachmentsFieldIsDisabledWhenEnabledAttachmentsFallback(): void
    {
        $localization = $this->getReference(LoadLocalizationData::ES_LOCALIZATION_CODE);

        $emailTemplateTranslation = new EmailTemplateTranslation();
        $emailTemplateTranslation->setLocalization($localization);
        $emailTemplateTranslation->setSubject('Test Email Subject');
        $emailTemplateTranslation->setContent('Test Email Content');
        $emailTemplateAttachment = new EmailTemplateAttachment();
        $emailTemplateAttachment->setFilePlaceholder('entity.avatar');
        $emailTemplateTranslation->addAttachment($emailTemplateAttachment);
        $emailTemplateTranslation->setAttachmentsFallback(true);

        $form = self::createForm(EmailTemplateTranslationType::class, $emailTemplateTranslation, [
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
            'localization' => $localization,
        ]);

        // Check that the attachments field is disabled
        self::assertTrue(
            $form->get('attachments')->isDisabled(),
            'Attachments field should be disabled when attachmentsFallback is true'
        );

        // Check that attachments fields is empty
        self::assertCount(
            0,
            $form->get('attachments')->getData(),
            'Attachments should be empty when attachmentsFallback is true'
        );
    }

    public function testAttachmentsFieldIsNotDisabledWhenDisabledAttachmentsFallback(): void
    {
        $localization = $this->getReference(LoadLocalizationData::ES_LOCALIZATION_CODE);

        $emailTemplateTranslation = new EmailTemplateTranslation();
        $emailTemplateTranslation->setLocalization($localization);
        $emailTemplateTranslation->setSubject('Test Email Subject');
        $emailTemplateTranslation->setContent('Test Email Content');
        $emailTemplateAttachment = new EmailTemplateAttachment();
        $emailTemplateAttachment->setFilePlaceholder('entity.avatar');
        $emailTemplateTranslation->addAttachment($emailTemplateAttachment);
        $emailTemplateTranslation->setAttachmentsFallback(false);

        $form = self::createForm(EmailTemplateTranslationType::class, $emailTemplateTranslation, [
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
            'localization' => $localization,
        ]);

        // Check that the attachments field is enabled
        self::assertFalse(
            $form->get('attachments')->isDisabled(),
            'Attachments field should be enabled when attachmentsFallback is false'
        );

        // Check that attachments fields is not empty.
        self::assertCount(
            1,
            $form->get('attachments')->getData(),
            'Attachments should not be empty when attachmentsFallback is false'
        );
    }

    public function testViewVarsWhenNoLocalization(): void
    {
        $form = self::createForm(EmailTemplateTranslationType::class, null, [
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => [],
            'localization' => null,
        ]);

        $formView = $form->createView();

        // When no localization is provided, these view vars should be null
        self::assertNull($formView->vars['localization_id']);
        self::assertNull($formView->vars['localization_title']);
        self::assertNull($formView->vars['localization_parent_id']);

        // Check block prefix
        self::assertContains('oro_email_emailtemplate_localization', $formView->vars['block_prefixes']);
    }

    public function testViewVarsWhenHasLocalization(): void
    {
        // Get a localization without a parent
        $localization = $this->getReference(LoadLocalizationData::ES_LOCALIZATION_CODE);

        $form = self::createForm(EmailTemplateTranslationType::class, null, [
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => [],
            'localization' => $localization,
        ]);

        $formView = $form->createView();

        // When localization is provided, view vars should contain localization information
        self::assertEquals($localization->getId(), $formView->vars['localization_id']);
        self::assertEquals($localization->getName(), $formView->vars['localization_title']);

        // This localization doesn't have a parent
        self::assertNull($formView->vars['localization_parent_id']);

        // Check block prefix
        self::assertContains('oro_email_emailtemplate_localization', $formView->vars['block_prefixes']);
    }

    public function testViewVarsWhenHasLocalizationWithParent(): void
    {
        // Get a localization with a parent
        $localization = $this->getReference(LoadLocalizationData::EN_CA_LOCALIZATION_CODE);
        $parentLocalization = $localization->getParentLocalization();

        $form = self::createForm(EmailTemplateTranslationType::class, null, [
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => [],
            'localization' => $localization,
        ]);

        $formView = $form->createView();

        // When localization with parent is provided, view vars should contain localization information
        self::assertEquals($localization->getId(), $formView->vars['localization_id']);
        self::assertEquals($localization->getName(), $formView->vars['localization_title']);

        // Parent localization ID should be set
        self::assertNotNull($formView->vars['localization_parent_id']);
        self::assertEquals($parentLocalization->getId(), $formView->vars['localization_parent_id']);

        // Check block prefix
        self::assertContains('oro_email_emailtemplate_localization', $formView->vars['block_prefixes']);
    }

    public function testSubmitWhenNoLocalization(): void
    {
        $form = self::createForm(EmailTemplateTranslationType::class, null, [
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
        ]);

        $formData = [
            'subject' => 'Test Email Subject',
            'content' => 'Test Email Content',
            'attachments' => [],
        ];

        $form->submit($formData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSubmitted());

        /** @var EmailTemplateTranslation $emailTemplateTranslation */
        $emailTemplateTranslation = $form->getData();

        self::assertInstanceOf(EmailTemplateTranslation::class, $emailTemplateTranslation);
        self::assertEquals($formData['subject'], $emailTemplateTranslation->getSubject());
        self::assertEquals($formData['content'], $emailTemplateTranslation->getContent());
        self::assertCount(0, $emailTemplateTranslation->getAttachments());

        // Since we didn't specify a localization, there should be no localization
        self::assertNull($emailTemplateTranslation->getLocalization());
    }

    public function testSubmitWhenHasLocalization(): void
    {
        $localization = $this->getReference(LoadLocalizationData::ES_LOCALIZATION_CODE);

        $form = self::createForm(EmailTemplateTranslationType::class, null, [
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
            'localization' => $localization,
        ]);

        $formData = [
            'subject' => 'Test Localized Subject',
            'content' => 'Test Localized Content',
            'attachments' => [],
            'subjectFallback' => false,
            'contentFallback' => false,
            'attachmentsFallback' => false,
        ];

        $form->submit($formData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSubmitted());

        /** @var EmailTemplateTranslation $emailTemplateTranslation */
        $emailTemplateTranslation = $form->getData();

        self::assertInstanceOf(EmailTemplateTranslation::class, $emailTemplateTranslation);
        self::assertEquals($formData['subject'], $emailTemplateTranslation->getSubject());
        self::assertEquals($formData['content'], $emailTemplateTranslation->getContent());
        self::assertFalse($emailTemplateTranslation->isSubjectFallback());
        self::assertFalse($emailTemplateTranslation->isContentFallback());
        self::assertFalse($emailTemplateTranslation->isAttachmentsFallback());
        self::assertCount(0, $emailTemplateTranslation->getAttachments());

        // The localization should be set on the entity
        self::assertNotNull($emailTemplateTranslation->getLocalization());
        self::assertSame($localization, $emailTemplateTranslation->getLocalization());
    }

    public function testSubmitWhenHasAttachments(): void
    {
        $form = self::createForm(EmailTemplateTranslationType::class, null, [
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
            'entity_class' => User::class,
        ]);

        $mockFile = [
            'file' => null,
            'filePlaceholder' => 'entity.avatar',
        ];

        $formData = [
            'subject' => 'Test Email with Attachments',
            'content' => 'Email content with attachments',
            'attachments' => [$mockFile],
        ];

        $form->submit($formData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSubmitted());

        /** @var EmailTemplateTranslation $emailTemplateTranslation */
        $emailTemplateTranslation = $form->getData();

        self::assertInstanceOf(EmailTemplateTranslation::class, $emailTemplateTranslation);
        self::assertEquals($formData['subject'], $emailTemplateTranslation->getSubject());
        self::assertEquals($formData['content'], $emailTemplateTranslation->getContent());

        // Verify attachments
        self::assertCount(1, $emailTemplateTranslation->getAttachments());

        $attachments = $emailTemplateTranslation->getAttachments()->toArray();

        self::assertEquals($mockFile['filePlaceholder'], $attachments[0]->getFilePlaceholder());

        // Verify attachment has a reference to the translation
        self::assertSame($emailTemplateTranslation, $attachments[0]->getTranslation());
    }

    public function testSubmitRequiresSubjectWhenSubjectFallback(): void
    {
        $localization = $this->getReference(LoadLocalizationData::ES_LOCALIZATION_CODE);

        // Re-create the form for the second test
        $form = self::createForm(EmailTemplateTranslationType::class, null, [
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
            'localization' => $localization,
        ]);

        // Submit the form with subjectFallback=false, which means subject is required
        $formDataWithoutFallback = [
            'subject' => '',  // Empty subject
            'content' => 'Test Content',
            'attachments' => [],
            'subjectFallback' => false,
            'contentFallback' => false,
            'attachmentsFallback' => false,
        ];

        $form->submit($formDataWithoutFallback);

        // Form should be invalid because subject is required when not using fallback
        self::assertFalse($form->isValid());
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->get('subject')->getErrors()->count() > 0);

        // Verify the error message is related to the NotBlank constraint
        $errorList = $form->get('subject')->getErrors();
        self::assertStringContainsString(
            'This value should not be blank',
            (string)$form->get('subject')->getErrors(),
            'NotBlank error not found for the subject field'
        );
    }

    public function testSubmitClearsAttachmentsWhenAttachmentsFallback(): void
    {
        $localization = $this->getReference(LoadLocalizationData::ES_LOCALIZATION_CODE);

        $emailTemplateTranslation = new EmailTemplateTranslation();
        $emailTemplateTranslation->setLocalization($localization);

        $attachment1 = new EmailTemplateAttachment();
        $attachment1->setFilePlaceholder('entity.avatar');
        $emailTemplateTranslation->addAttachment($attachment1);

        $attachment2 = new EmailTemplateAttachment();
        $attachment2->setFilePlaceholder('entity.avatar');
        $emailTemplateTranslation->addAttachment($attachment2);

        self::assertCount(2, $emailTemplateTranslation->getAttachments());

        $form = self::createForm(EmailTemplateTranslationType::class, $emailTemplateTranslation, [
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
            'localization' => $localization,
        ]);

        // Submit data with attachmentsFallback=true
        $formData = [
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'attachments' => [
                // This should be ignored when attachmentsFallback=true
                [
                    'file' => null,
                    'filePlaceholder' => 'entity.avatar',
                ],
            ],
            'subjectFallback' => false,
            'contentFallback' => false,
            'attachmentsFallback' => true, // Use fallback for attachments
        ];

        $form->submit($formData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSubmitted());

        /** @var EmailTemplateTranslation $result */
        $result = $form->getData();

        // Verify attachments were cleared
        self::assertCount(0, $result->getAttachments());
        self::assertTrue($result->isAttachmentsFallback());

        // Verify other fields are correctly set
        self::assertEquals($formData['subject'], $result->getSubject());
        self::assertEquals($formData['content'], $result->getContent());
        self::assertFalse($result->isSubjectFallback());
        self::assertFalse($result->isContentFallback());
    }
}
