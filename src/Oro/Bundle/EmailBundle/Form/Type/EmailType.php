<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\ActivityBundle\Form\Type\ContextsSelectType;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Form type to represent email
 */
class EmailType extends AbstractType
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var EmailRenderer */
    protected $emailRenderer;

    /** @var EmailModelBuilderHelper */
    protected $emailModelBuilderHelper;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param EmailRenderer                 $emailRenderer
     * @param EmailModelBuilderHelper       $emailModelBuilderHelper
     * @param ConfigManager                 $configManager
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        EmailRenderer $emailRenderer,
        EmailModelBuilderHelper $emailModelBuilderHelper,
        ConfigManager $configManager
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->emailRenderer = $emailRenderer;
        $this->emailModelBuilderHelper = $emailModelBuilderHelper;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gridName', HiddenType::class, ['required' => false])
            ->add('entityClass', HiddenType::class, ['required' => false])
            ->add('entityId', HiddenType::class, ['required' => false])
            ->add(
                'from',
                HiddenType::class
            )
            ->add(
                'origin',
                EmailOriginFromType::class,
                [
                    'required' => true,
                    'label' => 'oro.email.from_email_address.label',
                    'attr' => ['class' => 'from taggable-field']
                ]
            )
            ->add(
                'to',
                EmailAddressRecipientsType::class,
                [
                    'required' => false,
                    'attr' => ['class' => 'taggable-field forged-required']
                ]
            )
            ->add(
                'cc',
                EmailAddressRecipientsType::class,
                ['required' => false, 'attr' => ['class' => 'taggable-field'], 'label' => 'oro.email.cc.label']
            )
            ->add(
                'bcc',
                EmailAddressRecipientsType::class,
                ['required' => false, 'attr' => ['class' => 'taggable-field'], 'label' => 'oro.email.bcc.label']
            )
            ->add('subject', TextType::class, ['required' => true, 'label' => 'oro.email.subject.label'])
            ->add(
                'body',
                OroResizeableRichTextType::class,
                [
                    'required' => false,
                    'label' => 'oro.email.email_body.label',
                    'wysiwyg_options' => $this->getWysiwygOptions(),
                ]
            )
            ->add(
                'template',
                EmailTemplateSelectType::class,
                [
                    'label' => 'oro.email.template.label',
                    'required' => false,
                    'depends_on_parent_field' => 'entityClass',
                    'configs' => [
                        'allowClear' => true
                    ]
                ]
            )
            ->add(
                'type',
                ChoiceType::class,
                [
                    'label'      => 'oro.email.type.label',
                    'required'   => true,
                    'data'       => 'html',
                    'choices'  => [
                        'oro.email.datagrid.emailtemplate.filter.type.html' => 'html',
                        'oro.email.datagrid.emailtemplate.filter.type.txt' => 'txt',
                    ],
                    'expanded'   => true
                ]
            )
            ->add('attachments', EmailAttachmentsType::class, [
                'entry_type' => EmailAttachmentType::class,
                'required' => false,
                'allow_add' => true,
                'prototype' => false,
                'constraints' => [
                    new Valid()
                ],
                'entry_options' => [
                    'required' => false,
                ],
            ])
            ->add('bodyFooter', HiddenType::class)
            ->add('parentEmailId', HiddenType::class)
            ->add('signature', HiddenType::class)
            ->add(
                'contexts',
                ContextsSelectType::class,
                [
                    'label' => "oro.email.contexts.label",
                    'collectionModel' => true,
                    'error_bubbling'  => false,
                    'tooltip'   => 'oro.email.contexts.tooltip',
                    'configs'   => [
                        'containerCssClass' => 'taggable-email',
                        'route_name'       => 'oro_activity_form_autocomplete_search',
                        'route_parameters' => [
                            'activity' => 'emails',
                            'name'     => 'emails'
                        ],
                    ],
                    'attr' => [
                        'readonly' => !$this->authorizationChecker->isGranted(
                            'EDIT',
                            'entity:Oro\Bundle\EmailBundle\Entity\EmailUser'
                        )
                    ]
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'initChoicesByEntityName']);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'fillFormByTemplate']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'initChoicesByEntityName']);
    }

    /**
     * @param FormEvent $event
     */
    public function initChoicesByEntityName(FormEvent $event)
    {
        /** @var Email|array $data */
        $data = $event->getData();
        if (null === $data ||
            is_array($data) && empty($data['entityClass']) ||
            is_object($data) && null === $data->getEntityClass()) {
            return;
        }

        if (is_array($data) && isset($data['origin'])) {
            $value = $data['origin'];
            $values =  explode('|', $value);
            $data['from'] = $values[1];
            $this->emailModelBuilderHelper->preciseFullEmailAddress($data['from']);

            $event->setData($data);
        }

        $form = $event->getForm();
        if (!$form->has('template')) {
            return;
        }

        $entityClass = is_object($data) ? $data->getEntityClass() : $data['entityClass'];
        FormUtils::replaceField(
            $form,
            'template',
            [
                'selectedEntity' => $entityClass,
                'query_builder'  =>
                    function (EmailTemplateRepository $templateRepository) use ($entityClass) {
                        return $templateRepository->getEntityTemplatesQueryBuilder(
                            $entityClass,
                            $this->tokenAccessor->getOrganization(),
                            true
                        );
                    },
            ],
            ['choices']
        );
    }

    /**
     * @param FormEvent $event
     */
    public function fillFormByTemplate(FormEvent $event)
    {
        /** @var Email|null $data */
        $data = $event->getData();
        if (null === $data || !is_object($data) || null === $data->getTemplate()) {
            return;
        }

        if (null !== $data->getSubject() && null !== $data->getBody()) {
            return;
        }

        $emailTemplate = $data->getTemplate();

        $targetEntity = $this->emailModelBuilderHelper->getTargetEntity($data->getEntityClass(), $data->getEntityId());

        list($emailSubject, $emailBody) = $this->emailRenderer
            ->compileMessage($emailTemplate, ['entity' => $targetEntity]);

        if (null === $data->getSubject()) {
            $data->setSubject($emailSubject);
        }
        if (null === $data->getBody()) {
            $data->setBody($emailBody);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'Oro\Bundle\EmailBundle\Form\Model\Email',
                'csrf_token_id'      => 'email',
                'csrf_protection'    => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_email_email';
    }

    /**
     * @return array
     */
    protected function getWysiwygOptions()
    {
        if ($this->configManager->get('oro_email.sanitize_html')) {
            return [];
        }

        return [
            'valid_elements' => null, //all elements are valid
            'plugins' => array_merge(OroRichTextType::$defaultPlugins, ['fullpage']),
        ];
    }
}
