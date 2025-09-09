<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentsType;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailAttachmentsTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([LoadUser::class]);
    }

    public function testGetParent(): void
    {
        $formType = new EmailAttachmentsType();

        self::assertEquals(CollectionType::class, $formType->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        $formType = new EmailAttachmentsType();

        self::assertEquals('oro_email_attachments', $formType->getBlockPrefix());
    }

    public function testHasOptions(): void
    {
        $form = self::createForm(EmailAttachmentsType::class);

        self::assertFormOptions($form, [
            'allow_delete' => true,
        ]);
    }

    public function testDefaultOptions(): void
    {
        $form = self::createForm(EmailAttachmentsType::class, null, [
            'entry_type' => EmailAttachmentType::class,
            'required' => false,
            'allow_add' => true,
            'prototype' => false,
            'constraints' => [
                new Valid(),
            ],
            'entry_options' => [
                'required' => false,
            ],
        ]);

        self::assertFormOptions($form, [
            'entry_type' => EmailAttachmentType::class,
            'required' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => false,
            'constraints' => [new Valid()],
            'entry_options' => ['required' => false],
        ]);
    }

    public function testFinishViewWithEmailModelAttachments(): void
    {
        $emailAttachment1 = new EmailAttachment();
        $emailAttachment1->setId(1)
            ->setType(EmailAttachment::TYPE_UPLOADED)
            ->setFileName('test1.pdf')
            ->setIcon('fa-file-pdf')
            ->setErrors(['Error 1']);

        $emailAttachment2 = new EmailAttachment();
        $emailAttachment2->setId(2)
            ->setType(EmailAttachment::TYPE_EMAIL_ATTACHMENT)
            ->setFileName('test2.txt')
            ->setIcon('fa-file-text')
            ->setErrors([]);

        $attachments = new ArrayCollection([$emailAttachment1, $emailAttachment2]);

        $emailModel = new EmailModel();
        $emailModel->setAttachments($attachments->toArray());

        $form = self::createForm(EmailAttachmentsType::class, $attachments);

        $view = $form->createView();

        self::assertArrayHasKey('entity_attachments_array', $view->vars);
        self::assertCount(2, $view->vars['entity_attachments_array']);

        $expectedAttachment1 = [
            'id' => 1, // No index suffix for non-template attachments
            'type' => EmailAttachment::TYPE_UPLOADED,
            'fileName' => 'test1.pdf',
            'icon' => 'fa-file-pdf',
            'errors' => ['Error 1'],
        ];

        $expectedAttachment2 = [
            'id' => 2, // No index suffix for non-template attachments
            'type' => EmailAttachment::TYPE_EMAIL_ATTACHMENT,
            'fileName' => 'test2.txt',
            'icon' => 'fa-file-text',
            'errors' => [],
        ];

        self::assertEquals($expectedAttachment1, $view->vars['entity_attachments_array'][0]);
        self::assertEquals($expectedAttachment2, $view->vars['entity_attachments_array'][1]);
    }

    public function testFinishViewWithEmailTemplateAttachments(): void
    {
        $emailTemplateAttachment1 = new EmailAttachment();
        $emailTemplateAttachment1->setId(10)
            ->setType(EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT)
            ->setFileName('template1.pdf')
            ->setIcon('fa-file-pdf')
            ->setErrors([]);

        $emailTemplateAttachment2 = new EmailAttachment();
        $emailTemplateAttachment2->setId(20)
            ->setType(EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT)
            ->setFileName('template2.txt')
            ->setIcon('fa-file-text')
            ->setErrors([]);

        $attachments = new ArrayCollection([$emailTemplateAttachment1, $emailTemplateAttachment2]);

        $form = self::createForm(EmailAttachmentsType::class, $attachments);

        $view = $form->createView();

        self::assertArrayHasKey('entity_attachments_array', $view->vars);
        self::assertCount(2, $view->vars['entity_attachments_array']);

        $expectedAttachment1 = [
            'id' => '10:0', // Index suffix for template attachments
            'type' => EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT,
            'fileName' => 'template1.pdf',
            'icon' => 'fa-file-pdf',
            'errors' => [],
        ];

        $expectedAttachment2 = [
            'id' => '20:0', // Index suffix for template attachments
            'type' => EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT,
            'fileName' => 'template2.txt',
            'icon' => 'fa-file-text',
            'errors' => [],
        ];

        self::assertEquals($expectedAttachment1, $view->vars['entity_attachments_array'][0]);
        self::assertEquals($expectedAttachment2, $view->vars['entity_attachments_array'][1]);
    }

    public function testFinishViewWithAttachmentsAvailable(): void
    {
        $availableAttachment1 = new EmailAttachment();
        $availableAttachment1->setId(10)
            ->setType(EmailAttachment::TYPE_ATTACHMENT)
            ->setFileName('available1.jpg')
            ->setFileSize(2048)
            ->setModified('2023-01-01 12:00:00')
            ->setIcon('fa-file-image')
            ->setPreview('/path/to/preview1.jpg');

        $availableAttachment2 = new EmailAttachment();
        $availableAttachment2->setId(20)
            ->setType(EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT)
            ->setFileName('available2.doc')
            ->setFileSize(4096)
            ->setModified('2023-01-02 14:30:00')
            ->setIcon('fa-file-word')
            ->setPreview('/path/to/preview2.jpg');

        $attachmentsAvailable = [$availableAttachment1, $availableAttachment2];

        $emailModel = new EmailModel();
        $emailModel->setAttachmentsAvailable($attachmentsAvailable);

        $parentForm = self::createForm(FormType::class, $emailModel, ['data_class' => EmailModel::class]);
        $parentForm->add('attachments', EmailAttachmentsType::class);

        $form = $parentForm->get('attachments');

        $view = $form->createView();

        self::assertArrayHasKey('attachments_available_array', $view->vars);
        self::assertCount(2, $view->vars['attachments_available_array']);

        $expectedAvailable1 = [
            'id' => 10,
            'type' => EmailAttachment::TYPE_ATTACHMENT,
            'fileName' => 'available1.jpg',
            'fileSize' => 2048,
            'modified' => '2023-01-01 12:00:00',
            'icon' => 'fa-file-image',
            'preview' => '/path/to/preview1.jpg',
        ];

        $expectedAvailable2 = [
            'id' => 20,
            'type' => EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT,
            'fileName' => 'available2.doc',
            'fileSize' => 4096,
            'modified' => '2023-01-02 14:30:00',
            'icon' => 'fa-file-word',
            'preview' => '/path/to/preview2.jpg',
        ];

        self::assertEquals($expectedAvailable1, $view->vars['attachments_available_array'][0]);
        self::assertEquals($expectedAvailable2, $view->vars['attachments_available_array'][1]);
    }

    public function testFinishViewWithoutEmailModel(): void
    {
        $form = self::createForm(EmailAttachmentsType::class, new ArrayCollection());

        $view = $form->createView();

        self::assertArrayHasKey('entity_attachments_array', $view->vars);
        self::assertArrayHasKey('attachments_available_array', $view->vars);
        self::assertEmpty($view->vars['entity_attachments_array']);
        self::assertEmpty($view->vars['attachments_available_array']);
    }

    public function testFinishViewWithNullAttachments(): void
    {
        $attachments = new ArrayCollection([null, null]);

        $form = self::createForm(EmailAttachmentsType::class, $attachments);

        $view = $form->createView();

        self::assertArrayHasKey('entity_attachments_array', $view->vars);
        self::assertEmpty($view->vars['entity_attachments_array']);
        self::assertArrayHasKey('attachments_available_array', $view->vars);
        self::assertEmpty($view->vars['attachments_available_array']);
    }

    public function testFinishViewWithMixedValidAndNullAttachments(): void
    {
        $validAttachment = new EmailAttachment();
        $validAttachment->setId(1)
            ->setType(EmailAttachment::TYPE_UPLOADED)
            ->setFileName('valid.pdf')
            ->setIcon('fa-file-pdf')
            ->setErrors([]);

        $attachments = new ArrayCollection([null, $validAttachment, null]);

        $form = self::createForm(EmailAttachmentsType::class, $attachments);

        $view = $form->createView();

        self::assertArrayHasKey('entity_attachments_array', $view->vars);
        self::assertCount(1, $view->vars['entity_attachments_array']);

        $expectedAttachment = [
            'id' => 1, // No index suffix for non-template attachments
            'type' => EmailAttachment::TYPE_UPLOADED,
            'fileName' => 'valid.pdf',
            'icon' => 'fa-file-pdf',
            'errors' => [],
        ];

        self::assertEquals($expectedAttachment, $view->vars['entity_attachments_array'][0]);
    }

    public function testFinishViewWithMixedAttachmentTypes(): void
    {
        $uploadedAttachment = new EmailAttachment();
        $uploadedAttachment->setId(1)
            ->setType(EmailAttachment::TYPE_UPLOADED)
            ->setFileName('uploaded.pdf')
            ->setIcon('fa-file-pdf')
            ->setErrors([]);

        $templateAttachment = new EmailAttachment();
        $templateAttachment->setId(2)
            ->setType(EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT)
            ->setFileName('template.doc')
            ->setIcon('fa-file-word')
            ->setErrors([]);

        $emailAttachment = new EmailAttachment();
        $emailAttachment->setId(3)
            ->setType(EmailAttachment::TYPE_EMAIL_ATTACHMENT)
            ->setFileName('email.txt')
            ->setIcon('fa-file-text')
            ->setErrors([]);

        $attachments = new ArrayCollection([$uploadedAttachment, $templateAttachment, $emailAttachment]);

        $form = self::createForm(EmailAttachmentsType::class, $attachments);

        $view = $form->createView();

        self::assertArrayHasKey('entity_attachments_array', $view->vars);
        self::assertCount(3, $view->vars['entity_attachments_array']);

        // Only template attachment should have index suffix
        $expectedUploadedAttachment = [
            'id' => 1,
            'type' => EmailAttachment::TYPE_UPLOADED,
            'fileName' => 'uploaded.pdf',
            'icon' => 'fa-file-pdf',
            'errors' => [],
        ];

        $expectedTemplateAttachment = [
            'id' => '2:0', // Index suffix only for template attachment
            'type' => EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT,
            'fileName' => 'template.doc',
            'icon' => 'fa-file-word',
            'errors' => [],
        ];

        $expectedEmailAttachment = [
            'id' => 3,
            'type' => EmailAttachment::TYPE_EMAIL_ATTACHMENT,
            'fileName' => 'email.txt',
            'icon' => 'fa-file-text',
            'errors' => [],
        ];

        self::assertEquals($expectedUploadedAttachment, $view->vars['entity_attachments_array'][0]);
        self::assertEquals($expectedTemplateAttachment, $view->vars['entity_attachments_array'][1]);
        self::assertEquals($expectedEmailAttachment, $view->vars['entity_attachments_array'][2]);
    }

    public function testSubmitWhenEmpty(): void
    {
        $form = self::createForm(EmailAttachmentsType::class, new ArrayCollection());

        $form->submit([]);

        self::assertEquals(new ArrayCollection(), $form->getData());
    }

    public function testSanitizeAttachmentsRemovesNullValues(): void
    {
        $attachment1 = new EmailAttachment();
        $attachment1->setId(1)->setFileName('valid.txt');

        $attachment3 = new EmailAttachment();
        $attachment3->setId(3)->setFileName('another-valid.pdf');

        $originalCollection = new ArrayCollection([
            $attachment1,
            null,
            $attachment3,
            null,
        ]);

        $form = self::createForm(EmailAttachmentsType::class, $originalCollection);

        // Simulate form submission with some null values
        $form->submit([
            ['id' => 1, 'fileName' => 'valid.txt'],
            null,
            ['id' => 3, 'fileName' => 'another-valid.pdf'],
            null,
        ]);

        $result = $form->getData();

        self::assertInstanceOf(ArrayCollection::class, $result);

        // Check that null values were removed
        $hasNullValues = false;
        foreach ($result as $item) {
            if ($item === null) {
                $hasNullValues = true;
                break;
            }
        }

        self::assertFalse($hasNullValues, 'Null values should be removed from the collection');
    }
}
