<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Form\EventListener\UserSubscriber;
use Oro\Bundle\UserBundle\Entity\User;

class UserType extends AbstractType
{
    /** @var SecurityContextInterface */
    protected $security;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var bool */
    protected $isMyProfilePage;

    /** ConfigManager */
    protected $userConfigManager;

    /**
     * @param SecurityContextInterface $security Security context
     * @param SecurityFacade           $securityFacade
     * @param Request                  $request Request
     * @param ConfigManager            $userConfigManager
     */
    public function __construct(
        SecurityContextInterface $security,
        SecurityFacade           $securityFacade,
        Request                  $request,
        ConfigManager            $userConfigManager
    ) {
        $this->security          = $security;
        $this->securityFacade    = $securityFacade;
        $this->userConfigManager = $userConfigManager;

        $this->isMyProfilePage = $request->attributes->get('_route') === 'oro_user_profile_update';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addEntityFields($builder);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData']);
    }

    /**
     * {@inheritdoc}
     */
    public function addEntityFields(FormBuilderInterface $builder)
    {
        // user fields
        $builder->addEventSubscriber(new UserSubscriber($builder->getFormFactory(), $this->security));
        $this->setDefaultUserFields($builder);
        if ($this->securityFacade->isGranted('oro_user_role_view')) {
            $builder->add(
                'roles',
                'entity',
                [
                    'property_path' => 'rolesCollection',
                    'label'         => 'oro.user.roles.label',
                    'class'         => 'OroUserBundle:Role',
                    'property'      => 'label',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('r')
                            ->where('r.role <> :anon')
                            ->setParameter('anon', User::ROLE_ANONYMOUS)
                            ->orderBy('r.label');
                    },
                    'multiple'      => true,
                    'expanded'      => true,
                    'required'      => !$this->isMyProfilePage,
                    'read_only'     => $this->isMyProfilePage,
                    'disabled'      => $this->isMyProfilePage,
                    'translatable_options' => false
                ]
            );
        }
        if ($this->securityFacade->isGranted('oro_user_group_view')) {
            $builder->add(
                'groups',
                'entity',
                [
                    'label'     => 'oro.user.groups.label',
                    'class'     => 'OroUserBundle:Group',
                    'property'  => 'name',
                    'multiple'  => true,
                    'expanded'  => true,
                    'required'  => false,
                    'read_only' => $this->isMyProfilePage,
                    'disabled'  => $this->isMyProfilePage,
                    'translatable_options' => false
                ]
            );
        }
        if ($this->securityFacade->isGranted('oro_organization_view')
            && $this->securityFacade->isGranted('oro_business_unit_view')
        ) {
            $builder->add(
                'businessUnits',
                'oro_type_business_unit_select_autocomplete',
                [
                    'required' => false,
                    'label' => 'oro.user.form.access_settings.label',
                    'autocomplete_alias' => 'business_units_owner_search_handler'
                ]
            );
        }
        $builder
            ->add(
                'plainPassword',
                'repeated',
                [
                    'label'          => 'oro.user.password.label',
                    'type'           => 'password',
                    'required'       => true,
                    'first_options'  => ['label' => 'oro.user.password.label'],
                    'second_options' => ['label' => 'oro.user.password_re.label'],
                ]
            )
            ->add(
                'emails',
                'collection',
                [
                    'label'          => 'oro.user.emails.label',
                    'type'           => 'oro_user_email',
                    'allow_add'      => true,
                    'allow_delete'   => true,
                    'by_reference'   => false,
                    'prototype'      => true,
                    'prototype_name' => 'tag__name__'
                ]
            );
        if ($this->userConfigManager->get('oro_imap.enable_google_imap')) {
            $builder->add(
                'imapAccountType',
                'oro_imap_choice_account_type',
                ['label' => 'oro.user.imap_configuration.label']
            );
        } else {
            $builder->add(
                'imapConfiguration',
                'oro_imap_configuration',
                ['label' => 'oro.user.imap_configuration.label']
            );
        }
        $builder->add('change_password', ChangePasswordType::NAME)
            ->add('avatar', 'oro_image', ['label' => 'oro.user.avatar.label', 'required' => false]);

        $this->addInviteUserField($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'           => 'Oro\Bundle\UserBundle\Entity\User',
                'intention'            => 'user',
                'validation_groups'    => function ($form) {
                    if ($form instanceof FormInterface) {
                        $user = $form->getData();
                    } elseif ($form instanceof FormView) {
                        $user = $form->vars['value'];
                    } else {
                        $user = null;
                    }

                    return $user && $user->getId()
                        ? ['Roles', 'Default']
                        : ['Registration', 'Roles', 'Default'];
                },
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'cascade_validation'   => true,
                'ownership_disabled'   => $this->isMyProfilePage
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_user_user';
    }

    /**
     * Set user fields
     *
     * @param FormBuilderInterface $builder
     */
    protected function setDefaultUserFields(FormBuilderInterface $builder)
    {
        $builder
            ->add('username', 'text', ['label' => 'oro.user.username.label', 'required' => true])
            ->add('email', 'email', ['label' => 'oro.user.email.label', 'required' => true])
            ->add('phone', 'text', ['label' => 'oro.user.phone.label', 'required' => false])
            ->add('namePrefix', 'text', ['label' => 'oro.user.name_prefix.label', 'required' => false])
            ->add('firstName', 'text', ['label' => 'oro.user.first_name.label', 'required' => true])
            ->add('middleName', 'text', ['label' => 'oro.user.middle_name.label', 'required' => false])
            ->add('lastName', 'text', ['label' => 'oro.user.last_name.label', 'required' => true])
            ->add('nameSuffix', 'text', ['label' => 'oro.user.name_suffix.label', 'required' => false])
            ->add('birthday', 'oro_date', ['label' => 'oro.user.birthday.label', 'required' => false]);
    }

    /**
     * Add Invite user fields
     *
     * @param FormBuilderInterface $builder
     */
    protected function addInviteUserField(FormBuilderInterface $builder)
    {
        $builder->add(
            'inviteUser',
            'checkbox',
            [
                'label'    => 'oro.user.invite.label',
                'mapped'   => false,
                'required' => false,
                'tooltip'  => 'oro.user.invite.tooltip',
                'data'     => true
            ]
        );
    }

    /**
     * Post set data handler
     *
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        $data = $form->getData();
        if ($data instanceof User) {
            $token = $this->security->getToken();
            if ($token && is_object($user = $token->getUser()) && $data->getId() == $user->getId()) {
                $form->add(
                    'signature',
                    'oro_rich_text',
                    [
                        'label'    => 'oro.user.form.signature.label',
                        'required' => false,
                        'mapped'   => false,
                        'data'     => $this->userConfigManager->get('oro_email.signature'),
                    ]
                );
            }
        }
    }
}
