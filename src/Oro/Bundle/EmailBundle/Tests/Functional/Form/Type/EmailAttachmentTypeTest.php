<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Type;

use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromSystemConfig;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentType;
use Oro\Bundle\EmailBundle\Tests\Functional\Form\Type\DataFixtures\LoadEmailAttachmentTypeData;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailAttachmentTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([LoadEmailAttachmentTypeData::class]);
    }

    public function testGetBlockPrefix(): void
    {
        $form = self::createForm(EmailAttachmentType::class);
        $formView = $form->createView();

        self::assertContains('oro_email_attachment', $formView->vars['block_prefixes']);
    }

    public function testHasFields(): void
    {
        $form = self::createForm(EmailAttachmentType::class);

        self::assertFormHasField($form, 'id', TextType::class);
        self::assertFormHasField($form, 'type', TextType::class, ['required' => true]);
        self::assertFormHasField($form, 'file', FileType::class, [
            'constraints' => [
                new FileConstraintFromSystemConfig([
                    'maxSizeConfigPath' => 'oro_email.attachment_max_size',
                ]),
            ],
        ]);
    }

    public function testConfigureOptions(): void
    {
        $form = self::createForm(EmailAttachmentType::class);

        self::assertFormOptions($form, [
            'data_class' => EmailAttachmentModel::class,
            'csrf_token_id' => 'email_attachment',
        ]);
    }

    public function testSubmitWithExistingEmailAttachment(): void
    {
        $emailAttachment = new EmailAttachment();
        $emailAttachment->setFileName('existing.pdf');

        $emailAttachmentModel = new EmailAttachmentModel();
        $emailAttachmentModel->setEmailAttachment($emailAttachment);

        $form = self::createForm(EmailAttachmentType::class, $emailAttachmentModel);

        $form->submit([
            'id' => 1,
            'type' => EmailAttachmentModel::TYPE_EMAIL_ATTACHMENT,
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());

        $result = $form->getData();
        self::assertInstanceOf(EmailAttachmentModel::class, $result);
        self::assertSame($emailAttachment, $result->getEmailAttachment());
        self::assertEquals('existing.pdf', $result->getFileName());
    }

    public function testSubmitWithAttachmentType(): void
    {
        $attachment = $this->getReference('test_attachment');

        $form = self::createForm(EmailAttachmentType::class);

        $form->submit([
            'id' => $attachment->getId(),
            'type' => EmailAttachmentModel::TYPE_ATTACHMENT,
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());

        $result = $form->getData();
        self::assertInstanceOf(EmailAttachmentModel::class, $result);
        self::assertInstanceOf(EmailAttachment::class, $result->getEmailAttachment());
        self::assertEquals('dummy.pdf', $result->getEmailAttachment()->getFileName());
    }

    public function testSubmitWithUploadedFile(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . DIRECTORY_SEPARATOR . 'DataFixtures' . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'dummy.pdf',
            'test.pdf',
            'application/pdf',
            null,
            true
        );

        $form = self::createForm(EmailAttachmentType::class);

        $form->submit([
            'id' => null,
            'type' => EmailAttachmentModel::TYPE_UPLOADED,
            'file' => $uploadedFile,
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());

        $result = $form->getData();
        self::assertInstanceOf(EmailAttachmentModel::class, $result);
        self::assertInstanceOf(EmailAttachment::class, $result->getEmailAttachment());
        self::assertEquals('test.pdf', $result->getEmailAttachment()->getFileName());
        self::assertEquals('application/pdf', $result->getEmailAttachment()->getContentType());
    }

    public function testSubmitWithEmailTemplateAttachment(): void
    {
        $emailTemplateAttachment = $this->getReference('test_template_attachment');

        $emailModel = new EmailModel();
        $emailModel->setEntityClass('TestEntity');
        $emailModel->setEntityId(1);

        $form = self::createForm(EmailAttachmentType::class);

        $form->submit([
            'id' => $emailTemplateAttachment->getId() . ':0', // Include index in ID format
            'type' => EmailAttachmentModel::TYPE_EMAIL_TEMPLATE_ATTACHMENT,
            'file' => null,
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());

        $result = $form->getData();
        self::assertInstanceOf(EmailAttachmentModel::class, $result);
        self::assertInstanceOf(EmailAttachment::class, $result->getEmailAttachment());
        self::assertEquals('dummy.pdf', $result->getEmailAttachment()->getFileName());
        self::assertEquals('application/pdf', $result->getEmailAttachment()->getContentType());
    }

    public function testSubmitWithEmailTemplateAttachmentInvalidIndex(): void
    {
        $emailTemplateAttachment = $this->getReference('test_template_attachment');

        $form = self::createForm(EmailAttachmentType::class);

        $form->submit([
            'id' => $emailTemplateAttachment->getId() . ':999', // Invalid index
            'type' => EmailAttachmentModel::TYPE_EMAIL_TEMPLATE_ATTACHMENT,
            'file' => null,
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());

        $result = $form->getData();
        self::assertInstanceOf(EmailAttachmentModel::class, $result);
        self::assertNull($result->getEmailAttachment()); // Should be null for invalid index
    }

    public function testSubmitWithEmailTemplateAttachmentNoIndex(): void
    {
        $emailTemplateAttachment = $this->getReference('test_template_attachment');

        $form = self::createForm(EmailAttachmentType::class);

        $form->submit([
            'id' => (string)$emailTemplateAttachment->getId(), // No index, should default to 0
            'type' => EmailAttachmentModel::TYPE_EMAIL_TEMPLATE_ATTACHMENT,
            'file' => null,
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());

        $result = $form->getData();
        self::assertInstanceOf(EmailAttachmentModel::class, $result);
        self::assertInstanceOf(EmailAttachment::class, $result->getEmailAttachment());
        self::assertEquals('dummy.pdf', $result->getEmailAttachment()->getFileName());
    }

    public function testSubmitWithNonExistentEmailTemplateAttachment(): void
    {
        $form = self::createForm(EmailAttachmentType::class);

        $form->submit([
            'id' => '999999:0', // Non-existent template attachment ID
            'type' => EmailAttachmentModel::TYPE_EMAIL_TEMPLATE_ATTACHMENT,
            'file' => null,
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());

        $result = $form->getData();
        self::assertInstanceOf(EmailAttachmentModel::class, $result);
        self::assertNull($result->getEmailAttachment()); // Should be null for non-existent template attachment
    }

    public function testSubmitWithUploadedTypeButNoFile(): void
    {
        $form = self::createForm(EmailAttachmentType::class);

        $form->submit([
            'id' => null,
            'type' => EmailAttachmentModel::TYPE_UPLOADED,
            'file' => null,
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());

        $result = $form->getData();
        self::assertInstanceOf(EmailAttachmentModel::class, $result);
        self::assertNull($result->getEmailAttachment());
    }

    public function testSubmitWithInvalidType(): void
    {
        $form = self::createForm(EmailAttachmentType::class);

        $form->submit([
            'id' => 1,
            'type' => 999, // Invalid type
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertFalse($form->isValid());
        $errors = $form->getErrors(true, false);
        self::assertCount(1, $errors);
        self::assertEquals(
            'Invalid attachment type: 999',
            $errors[0]->getMessage()
        );
    }

    public function testSubmitWithNullData(): void
    {
        $form = self::createForm(EmailAttachmentType::class);

        $form->submit(null);

        self::assertTrue($form->isSubmitted());
        self::assertFalse($form->isValid());
        $errors = $form->getErrors(true, false);
        self::assertCount(1, $errors);
        self::assertEquals(
            'Invalid attachment type: 0',
            $errors[0]->getMessage()
        );
    }
}
