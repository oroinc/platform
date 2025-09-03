<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateAttachmentType;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailTemplateAttachmentTypeTest extends WebTestCase
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
        $form = self::createForm(EmailTemplateAttachmentType::class);

        self::assertFormOptions($form, [
            'data_class' => EmailTemplateAttachment::class,
            'error_bubbling' => false,
        ]);

        self::assertFormHasField($form, 'file', FileType::class);
        self::assertFormHasField($form, 'filePlaceholder', Select2ChoiceType::class, [
            'required' => false,
            'placeholder' => 'oro.email.emailtemplateattachment.file_placeholder.placeholder',
            'empty_data' => null,
        ]);
    }

    public function testHasFieldsWithEntityClass(): void
    {
        $form = self::createForm(EmailTemplateAttachmentType::class, null, [
            'entity_class' => User::class,
        ]);

        self::assertFormOptions($form, [
            'data_class' => EmailTemplateAttachment::class,
            'error_bubbling' => false,
            'entity_class' => User::class,
        ]);

        self::assertFormHasField($form, 'file', FileType::class);
        self::assertFormHasField($form, 'filePlaceholder', Select2ChoiceType::class, [
            'required' => false,
            'placeholder' => 'oro.email.emailtemplateattachment.file_placeholder.placeholder',
            'empty_data' => null,
        ]);

        // Check that choices include entity variables and upload file option
        $filePlaceholderField = $form->get('filePlaceholder');
        $choices = $filePlaceholderField->getConfig()->getOption('choices');

        self::assertEquals([
            'Avatar' => 'entity.avatar',
            'oro.email.emailtemplateattachment.file_placeholder.choices.upload_file_group' => [
                'oro.email.emailtemplateattachment.file_placeholder.choices.upload_file' =>
                    EmailTemplateAttachmentType::UPLOAD_FILE,
            ],
        ], $choices);
    }

    public function testHasBlockPrefix(): void
    {
        $form = self::createForm(EmailTemplateAttachmentType::class);
        $formView = $form->createView();

        self::assertContains('oro_email_emailtemplate_attachment', $formView->vars['block_prefixes']);
    }

    public function testFilePlaceholderViewValueWhenHasFile(): void
    {
        $file = new File();
        $file->setOriginalFilename('test.pdf');

        $attachment = new EmailTemplateAttachment();
        $attachment->setFile($file);

        $form = self::createForm(EmailTemplateAttachmentType::class, $attachment);
        $formView = $form->createView();

        // When a file is present, the filePlaceholder view value should be set to UPLOAD_FILE constant
        self::assertEquals(
            EmailTemplateAttachmentType::UPLOAD_FILE,
            $formView['filePlaceholder']->vars['value']
        );
    }

    public function testFilePlaceholderViewValueWhenNoFile(): void
    {
        $attachment = new EmailTemplateAttachment();
        $attachment->setFilePlaceholder('entity.avatar');

        $form = self::createForm(EmailTemplateAttachmentType::class, $attachment);
        $formView = $form->createView();

        self::assertEquals($attachment->getFilePlaceholder(), $formView['filePlaceholder']->vars['data']);
        self::assertEmpty($formView['filePlaceholder']->vars['value']);
    }

    public function testSubmitWithFilePlaceholder(): void
    {
        $form = self::createForm(EmailTemplateAttachmentType::class, null, ['entity_class' => User::class]);

        $submitData = [
            'file' => null,
            'filePlaceholder' => 'entity.avatar',
        ];

        $form->submit($submitData);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true, true));
        self::assertTrue($form->isSubmitted());

        /** @var EmailTemplateAttachment $attachment */
        $attachment = $form->getData();

        self::assertInstanceOf(EmailTemplateAttachment::class, $attachment);
        self::assertEquals($submitData['filePlaceholder'], $attachment->getFilePlaceholder());
        self::assertInstanceOf(File::class, $attachment->getFile());
        self::assertNull($attachment->getFile()->getFile());
    }

    public function testSubmitWithFile(): void
    {
        $form = self::createForm(EmailTemplateAttachmentType::class);

        $uploadedFile = new UploadedFile(
            __DIR__ . DIRECTORY_SEPARATOR . 'DataFixtures' . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'dummy.pdf',
            'dummy.pdf',
            'application/pdf',
            null,
            true
        );

        $submitData = [
            'file' => ['file' => $uploadedFile],
            'filePlaceholder' => '',
        ];

        $form->submit($submitData);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true, true));
        self::assertTrue($form->isSubmitted());

        /** @var EmailTemplateAttachment $attachment */
        $attachment = $form->getData();

        self::assertInstanceOf(EmailTemplateAttachment::class, $attachment);
        self::assertEquals($submitData['filePlaceholder'], $attachment->getFilePlaceholder());

        // File should be set on the attachment
        self::assertInstanceOf(File::class, $attachment->getFile());
        self::assertSame($uploadedFile, $attachment->getFile()->getFile());
    }

    public function testSubmitNotValidWhenAllFieldsBlank(): void
    {
        $form = self::createForm(EmailTemplateAttachmentType::class);

        $submitData = [
            'file' => null,
            'filePlaceholder' => '',
        ];

        $form->submit($submitData);

        self::assertFalse($form->isValid(), 'Form should not be valid when all fields are blank');
        self::assertTrue($form->isSubmitted());

        $translator = self::getContainer()->get('translator');
        $expectedError = $translator->trans(
            'oro.email.validator.email_template_attachment.one_field_not_blank',
            [],
            'validators'
        );

        self::assertStringContainsString($expectedError, (string)$form->getErrors(true, true));
    }

    public function testSubmitNotValidWhenMoreThanOneFieldNotBlank(): void
    {
        $form = self::createForm(EmailTemplateAttachmentType::class, null, ['entity_class' => User::class]);
        $uploadedFile = new UploadedFile(
            __DIR__ . DIRECTORY_SEPARATOR . 'DataFixtures' . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'dummy.pdf',
            'dummy.pdf',
            'application/pdf',
            null,
            true
        );

        $submitData = [
            'file' => ['file' => $uploadedFile],
            'filePlaceholder' => 'entity.avatar',
        ];

        $form->submit($submitData);

        self::assertFalse($form->isValid(), 'Form should not be valid when all fields are blank');
        self::assertTrue($form->isSubmitted());

        $translator = self::getContainer()->get('translator');
        $expectedError = $translator->trans(
            'oro.email.validator.email_template_attachment.one_field_not_blank',
            [],
            'validators'
        );

        self::assertStringContainsString($expectedError, (string)$form->getErrors(true, true));
    }

    public function testPreSetDataWithExistingFileEntity(): void
    {
        $file = new File();
        $file->setOriginalFilename('test.pdf');

        $attachment = new EmailTemplateAttachment();
        $attachment->setFile($file);

        self::createForm(EmailTemplateAttachmentType::class, $attachment, ['entity_class' => User::class]);

        // After form creation, the filePlaceholder should be set to UPLOAD_FILE constant
        self::assertEquals(EmailTemplateAttachmentType::UPLOAD_FILE, $attachment->getFilePlaceholder());
    }

    public function testPreSubmitWithUploadFileConstant(): void
    {
        $form = self::createForm(EmailTemplateAttachmentType::class);

        $submitData = [
            'file' => null,
            'filePlaceholder' => EmailTemplateAttachmentType::UPLOAD_FILE,
        ];

        $form->submit($submitData);

        /** @var EmailTemplateAttachment $attachment */
        $attachment = $form->getData();

        // After submission, the filePlaceholder should be null when it was UPLOAD_FILE
        self::assertNull($attachment->getFilePlaceholder());
    }

    public function testLoadChoicesWithoutEntityClass(): void
    {
        $form = self::createForm(EmailTemplateAttachmentType::class, null, [
            'entity_class' => null,
        ]);

        $filePlaceholderField = $form->get('filePlaceholder');
        $choices = $filePlaceholderField->getConfig()->getOption('choices');

        self::assertEquals([
            'oro.email.emailtemplateattachment.file_placeholder.choices.upload_file_group' => [
                'oro.email.emailtemplateattachment.file_placeholder.choices.upload_file' =>
                    EmailTemplateAttachmentType::UPLOAD_FILE,
            ],
        ], $choices);
    }

    public function testLoadChoicesWithEntityClassThatHasNoFileVariables(): void
    {
        // Use an entity class that likely has no File-type variables
        $form = self::createForm(EmailTemplateAttachmentType::class, null, [
            'entity_class' => Organization::class,
        ]);

        $filePlaceholderField = $form->get('filePlaceholder');
        $choices = $filePlaceholderField->getConfig()->getOption('choices');

        self::assertEquals([
            'oro.email.emailtemplateattachment.file_placeholder.choices.upload_file_group' => [
                'oro.email.emailtemplateattachment.file_placeholder.choices.upload_file' =>
                    EmailTemplateAttachmentType::UPLOAD_FILE,
            ],
        ], $choices);
    }
}
