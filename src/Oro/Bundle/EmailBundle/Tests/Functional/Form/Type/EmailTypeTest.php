<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Type;

use Oro\Bundle\ActivityBundle\Form\Type\ContextsSelectType;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressRecipientsType;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentsType;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentType;
use Oro\Bundle\EmailBundle\Form\Type\EmailOriginFromType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateSelectType;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Constraints\Valid;

class EmailTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([
            LoadUser::class,
            LoadUserData::class,
            '@OroEmailBundle/Tests/Functional/Form/Type/DataFixtures/EmailType.yml',
        ]);

        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHasFields(): void
    {
        $form = $this->formFactory->create(EmailType::class);

        self::assertFormOptions($form, [
            'data_class' => EmailModel::class,
            'csrf_token_id' => 'email',
            'csrf_protection' => true,
        ]);

        self::assertFormHasField($form, 'gridName', HiddenType::class, ['required' => false]);
        self::assertFormHasField($form, 'entityClass', HiddenType::class, ['required' => false]);
        self::assertFormHasField($form, 'entityId', HiddenType::class, ['required' => false]);
        self::assertFormHasField($form, 'from', HiddenType::class);
        self::assertFormHasField(
            $form,
            'origin',
            EmailOriginFromType::class,
            [
                'required' => true,
                'label' => 'oro.email.from_email_address.label',
                'attr' => ['class' => 'from taggable-field'],
            ]
        );
        self::assertFormHasField(
            $form,
            'to',
            EmailAddressRecipientsType::class,
            [
                'required' => false,
                'attr' => ['class' => 'taggable-field forged-required'],
            ]
        );
        self::assertFormHasField(
            $form,
            'cc',
            EmailAddressRecipientsType::class,
            ['required' => false, 'attr' => ['class' => 'taggable-field'], 'label' => 'oro.email.cc.label']
        );
        self::assertFormHasField(
            $form,
            'bcc',
            EmailAddressRecipientsType::class,
            ['required' => false, 'attr' => ['class' => 'taggable-field'], 'label' => 'oro.email.bcc.label']
        );
        self::assertFormHasField(
            $form,
            'subject',
            TextType::class,
            ['required' => true, 'label' => 'oro.email.subject.label']
        );
        self::assertFormHasField(
            $form,
            'body',
            OroResizeableRichTextType::class,
            [
                'required' => false,
                'label' => 'oro.email.email_body.label',
                'wysiwyg_options' => [
                    'valid_elements' => null,
                    'plugins' => array_merge(OroRichTextType::$defaultPlugins, ['fullscreen']),
                    'extended_valid_elements' => 'style[type|media],'
                        . 'td[background|align|style|class|colspan|width|valign|height],'
                        . 'span[style]',
                    'custom_elements' => 'style'
                ],
            ]
        );
        self::assertFormHasField(
            $form,
            'template',
            EmailTemplateSelectType::class,
            [
                'label' => 'oro.email.template.label',
                'required' => false,
                'depends_on_parent_field' => 'entityClass',
                'configs' => [
                    'allowClear' => true,
                ],
            ]
        );
        self::assertFormHasField(
            $form,
            'type',
            ChoiceType::class,
            [
                'label' => 'oro.email.type.label',
                'required' => true,
                'data' => 'html',
                'choices' => [
                    'oro.email.datagrid.emailtemplate.filter.type.html' => 'html',
                    'oro.email.datagrid.emailtemplate.filter.type.txt' => 'txt',
                ],
                'expanded' => true,
            ]
        );
        self::assertFormHasField(
            $form,
            'attachments',
            EmailAttachmentsType::class,
            [
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
            ]
        );
        self::assertFormHasField($form, 'bodyFooter', HiddenType::class);
        self::assertFormHasField($form, 'parentEmailId', HiddenType::class);
        self::assertFormHasField($form, 'signature', HiddenType::class);
        self::assertFormHasField(
            $form,
            'contexts',
            ContextsSelectType::class,
            [
                'label' => "oro.email.contexts.label",
                'collectionModel' => true,
                'error_bubbling' => false,
                'tooltip' => 'oro.email.contexts.tooltip',
                'configs' => [
                    'containerCssClass' => 'taggable-email',
                    'route_name' => 'oro_activity_form_autocomplete_search',
                    'route_parameters' => [
                        'activity' => 'emails',
                        'name' => 'emails',
                    ],
                ],
                'attr' => [
                    'readonly' => false,
                ],
            ]
        );
    }

    public function testTemplateOptionsEmptyWhenNoEntityClass(): void
    {
        $emailModel = new EmailModel();
        $form = $this->formFactory->create(EmailType::class, $emailModel, ['csrf_protection' => false]);

        $formView = $form->createView();

        self::assertEquals(
            [],
            array_map(static fn (ChoiceView $choiceView) => $choiceView->label, $formView['template']->vars['choices'])
        );
    }

    public function testTemplateOptionsNotEmptyWhenHasEntityClass(): void
    {
        $emailModel = (new EmailModel())
            ->setEntityClass(User::class);
        $form = $this->formFactory->create(EmailType::class, $emailModel, ['csrf_protection' => false]);

        self::assertFormHasField(
            $form,
            'template',
            EmailTemplateSelectType::class,
            ['selectedEntity' => $emailModel->getEntityClass()]
        );

        $formView = $form->createView();

        $choices = array_map(
            static fn (ChoiceView $choiceView) => $choiceView->label,
            $formView['template']->vars['choices']
        );

        self::assertContains('email_template_1', $choices);
        self::assertContains('email_template_user', $choices);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmitWhenEmpty(?EmailModel $emailModel): void
    {
        $form = $this->formFactory->create(EmailType::class, $emailModel, ['csrf_protection' => false]);

        $form->submit([]);

        self::assertEquals(new EmailModel(), $form->getData());
    }

    public function submitDataProvider(): \Generator
    {
        yield 'when empty form data' => [
            'emailModel' => null,
        ];

        yield 'when empty email model' => [
            'emailModel' => new EmailModel(),
        ];
    }

    public function testSubmitWithEmailTemplate(): void
    {
        /** @var User $admin */
        $admin = $this->getReference(LoadUser::USER);
        /** @var User $user1 */
        $user1 = $this->getReference(LoadUserData::USER_NAME);
        /** @var User $user2 */
        $user2 = $this->getReference(LoadUserData::USER_NAME_2);
        /** @var EmailTemplateEntity $template */
        $template = $this->getReference('email_template_user');

        $emailModel = (new EmailModel())
            ->setGridName('test_grid')
            ->setEntityClass(User::class)
            ->setEntityId($user1->getId())
            ->setFrom('no-reply@example.com')
            ->setTo([$user1->getEmail()])
            ->setTemplate($template)
            ->setType($template->getType());

        $form = $this->formFactory->create(EmailType::class, $emailModel, ['csrf_protection' => false]);

        $form->submit([
            'gridName' => 'test_grid',
            'from' => 'no-reply@example.com',
            'to' => base64_encode((string)$user1->getId()),
            'cc' => base64_encode((string)$user2->getId()),
            'bcc' => base64_encode((string)$admin->getId()),
            'template' => $template->getId(),
            'subject' => $emailModel->getSubject(),
            'body' => $emailModel->getBody(),
            'type' => $emailModel->getType(),
            'bodyFooter' => 'sample footer',
            'signature' => 'sample signature',
        ]);

        $emailRenderer = self::getContainer()->get('oro_email.email_renderer');
        $renderedEmailTemplate = $emailRenderer->renderEmailTemplate($template, ['entity' => $user1]);

        $expectedEmailModel = (new EmailModel())
            ->setGridName('test_grid')
            ->setFrom('no-reply@example.com')
            ->setTo([$user1->getId()])
            ->setCc([$user2->getId()])
            ->setBcc([$admin->getId()])
            ->setTemplate($template)
            ->setSubject($renderedEmailTemplate->getSubject())
            ->setBody($renderedEmailTemplate->getContent())
            ->setType($renderedEmailTemplate->getType())
            ->setBodyFooter('sample footer')
            ->setSignature('sample signature');

        self::assertEquals($expectedEmailModel, $form->getData());
    }

    public function testSubmitWithoutEmailTemplate(): void
    {
        /** @var User $admin */
        $admin = $this->getReference(LoadUser::USER);
        /** @var User $user1 */
        $user1 = $this->getReference(LoadUserData::USER_NAME);
        /** @var User $user2 */
        $user2 = $this->getReference(LoadUserData::USER_NAME_2);

        $emailModel = (new EmailModel())
            ->setEntityClass(User::class)
            ->setEntityId($user1->getId())
            ->setFrom('no-reply@example.com')
            ->setTo([$user1->getEmail()]);

        $form = $this->formFactory->create(EmailType::class, $emailModel, ['csrf_protection' => false]);

        $form->submit([
            'from' => 'no-reply@example.com',
            'to' => base64_encode((string)$user1->getId()),
            'cc' => base64_encode((string)$user2->getId()),
            'bcc' => base64_encode((string)$admin->getId()),
            'template' => '',
            'subject' => 'sample subject',
            'body' => 'sample body',
            'type' => 'html',
            'bodyFooter' => 'sample footer',
            'signature' => 'sample signature',
        ]);

        $expectedEmailModel = (new EmailModel())
            ->setFrom('no-reply@example.com')
            ->setTo([$user1->getId()])
            ->setCc([$user2->getId()])
            ->setBcc([$admin->getId()])
            ->setSubject('sample subject')
            ->setBody('sample body')
            ->setType('html')
            ->setBodyFooter('sample footer')
            ->setSignature('sample signature');

        self::assertEquals($expectedEmailModel, $form->getData());
    }

    public function testSubmitWithEmailTemplateAndNotEmptySubject(): void
    {
        /** @var User $admin */
        $admin = $this->getReference(LoadUser::USER);
        /** @var User $user1 */
        $user1 = $this->getReference(LoadUserData::USER_NAME);
        /** @var User $user2 */
        $user2 = $this->getReference(LoadUserData::USER_NAME_2);
        /** @var EmailTemplateEntity $template */
        $template = $this->getReference('email_template_user');

        $emailModel = (new EmailModel())
            ->setEntityClass(User::class)
            ->setEntityId($user1->getId())
            ->setFrom('no-reply@example.com')
            ->setTo([$user1->getEmail()])
            ->setTemplate($template)
            ->setType($template->getType())
            ->setSubject('sample subject');

        $form = $this->formFactory->create(EmailType::class, $emailModel, ['csrf_protection' => false]);

        $form->submit([
            'from' => 'no-reply@example.com',
            'to' => base64_encode((string)$user1->getId()),
            'cc' => base64_encode((string)$user2->getId()),
            'bcc' => base64_encode((string)$admin->getId()),
            'template' => $template->getId(),
            'subject' => $emailModel->getSubject(),
            'body' => $emailModel->getBody(),
            'type' => $emailModel->getType(),
            'bodyFooter' => 'sample footer',
            'signature' => 'sample signature',
        ]);

        $emailRenderer = self::getContainer()->get('oro_email.email_renderer');
        $renderedEmailTemplate = $emailRenderer->renderEmailTemplate($template, ['entity' => $user1]);

        $expectedEmailModel = (new EmailModel())
            ->setFrom('no-reply@example.com')
            ->setTo([$user1->getId()])
            ->setCc([$user2->getId()])
            ->setBcc([$admin->getId()])
            ->setTemplate($template)
            ->setSubject('sample subject')
            ->setBody($renderedEmailTemplate->getContent())
            ->setType($renderedEmailTemplate->getType())
            ->setBodyFooter('sample footer')
            ->setSignature('sample signature');

        self::assertEquals($expectedEmailModel, $form->getData());
    }

    public function testSubmitWithEmailTemplateAndNotEmptySubjectAndBody(): void
    {
        /** @var User $admin */
        $admin = $this->getReference(LoadUser::USER);
        /** @var User $user1 */
        $user1 = $this->getReference(LoadUserData::USER_NAME);
        /** @var User $user2 */
        $user2 = $this->getReference(LoadUserData::USER_NAME_2);
        /** @var EmailTemplateEntity $template */
        $template = $this->getReference('email_template_user');

        $emailModel = (new EmailModel())
            ->setEntityClass(User::class)
            ->setEntityId($user1->getId())
            ->setFrom('no-reply@example.com')
            ->setTo([$user1->getEmail()])
            ->setTemplate($template)
            ->setType($template->getType())
            ->setSubject('sample subject')
            ->setBody('sample body');

        $form = $this->formFactory->create(EmailType::class, $emailModel, ['csrf_protection' => false]);

        $form->submit([
            'from' => 'no-reply@example.com',
            'to' => base64_encode((string)$user1->getId()),
            'cc' => base64_encode((string)$user2->getId()),
            'bcc' => base64_encode((string)$admin->getId()),
            'template' => $template->getId(),
            'subject' => $emailModel->getSubject(),
            'body' => $emailModel->getBody(),
            'type' => $emailModel->getType(),
            'bodyFooter' => 'sample footer',
            'signature' => 'sample signature',
        ]);

        $expectedEmailModel = (new EmailModel())
            ->setFrom('no-reply@example.com')
            ->setTo([$user1->getId()])
            ->setCc([$user2->getId()])
            ->setBcc([$admin->getId()])
            ->setTemplate($template)
            ->setSubject('sample subject')
            ->setBody('sample body')
            ->setType($template->getType())
            ->setBodyFooter('sample footer')
            ->setSignature('sample signature');

        self::assertEquals($expectedEmailModel, $form->getData());
    }
}
