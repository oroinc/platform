<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\FormBundle\Form\Type\OroBirthdayType;
use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationsSelectType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\UserSubscriber;
use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType as SymfonyEmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Form type of User entity
 */
class UserType extends AbstractType
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var bool */
    protected $isMyProfilePage;

    /** @var PasswordFieldOptionsProvider */
    protected $optionsProvider;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param RequestStack                  $requestStack
     * @param PasswordFieldOptionsProvider  $optionsProvider
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        RequestStack $requestStack,
        PasswordFieldOptionsProvider $optionsProvider
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;

        $this->isMyProfilePage = $requestStack->getCurrentRequest()->get('_route') === 'oro_user_profile_update';
        $this->optionsProvider = $optionsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addEntityFields($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function addEntityFields(FormBuilderInterface $builder)
    {
        // user fields
        $builder->addEventSubscriber(new UserSubscriber($builder->getFormFactory(), $this->tokenAccessor));
        $this->setDefaultUserFields($builder);
        $attr = [];

        if ($this->isMyProfilePage) {
            $attr['readonly'] = true;
        }

        if ($this->authorizationChecker->isGranted('oro_user_role_view')) {
            $builder->add(
                'roles',
                EntityType::class,
                [
                    'label'         => 'oro.user.roles.label',
                    'class'         => 'OroUserBundle:Role',
                    'choice_label'      => 'label',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('r')
                            ->where('r.role <> :anon')
                            ->setParameter('anon', User::ROLE_ANONYMOUS)
                            ->orderBy('r.label');
                    },
                    'multiple'      => true,
                    'expanded'      => true,
                    'required'      => !$this->isMyProfilePage,
                    'disabled'      => $this->isMyProfilePage,
                    'translatable_options' => false,
                    'attr' => $attr
                ]
            );
        }
        if ($this->authorizationChecker->isGranted('oro_user_group_view')) {
            $builder->add(
                'groups',
                EntityType::class,
                [
                    'label'     => 'oro.user.groups.label',
                    'class'     => 'OroUserBundle:Group',
                    'choice_label'  => 'name',
                    'multiple'  => true,
                    'expanded'  => true,
                    'required'  => false,
                    'disabled'  => $this->isMyProfilePage,
                    'translatable_options' => false,
                    'attr' => $attr
                ]
            );
        }
        $this->addOrganizationField($builder);
        $builder
            ->add(
                'emails',
                CollectionType::class,
                [
                    'label'          => 'oro.user.emails.label',
                    'entry_type'     => EmailType::class,
                    'allow_add'      => true,
                    'allow_delete'   => true,
                    'by_reference'   => false,
                    'prototype'      => true,
                    'prototype_name' => 'tag__name__'
                ]
            )
            ->add('change_password', ChangePasswordType::class)
            ->add('avatar', ImageType::class, ['label' => 'oro.user.avatar.label', 'required' => false]);

        $this->addInviteUserField($builder);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData'], 10);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var User $data */
        $data = $event->getData();
        if (!$data instanceof User) {
            return;
        }

        $passwordOptions = [
            'invalid_message' => 'oro.user.message.password_mismatch',
            'type' => PasswordType::class,
            'required' => false,
            'first_options' => [
                'label' => 'oro.user.password.label',
                'tooltip' => $this->optionsProvider->getTooltip(),
                'attr' => [
                    'autocomplete' => 'new-password',
                    'data-validation' => $this->optionsProvider->getDataValidationOption(),
                ]
            ],
            'second_options' => ['label' => 'oro.user.password_re.label'],
        ];

        if (!$data->getId()) {
            $form
                ->add(
                    'passwordGenerate',
                    CheckboxType::class,
                    [
                        'required' => false,
                        'label' => 'oro.user.password.password_generate.label',
                        'mapped' => false
                    ]
                );

            $passwordOptions = array_merge(
                $passwordOptions,
                [
                    'required' => true,
                    'validation_groups' => ['Registration'],
                ]
            );
        }

        $form->add('plainPassword', RepeatedType::class, $passwordOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'           => 'Oro\Bundle\UserBundle\Entity\User',
                'csrf_token_id'        => 'user',
                'validation_groups'    => ['Roles', 'Default'],
                'ownership_disabled'   => $this->isMyProfilePage
            ]
        );
    }

    /**
     * {@inheritdoc}
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
            ->add('username', TextType::class, ['label' => 'oro.user.username.label', 'required' => true])
            ->add('email', SymfonyEmailType::class, ['label' => 'oro.user.email.label', 'required' => true])
            ->add('phone', TextType::class, ['label' => 'oro.user.phone.label', 'required' => false])
            ->add('namePrefix', TextType::class, ['label' => 'oro.user.name_prefix.label', 'required' => false])
            ->add('firstName', TextType::class, ['label' => 'oro.user.first_name.label', 'required' => true])
            ->add('middleName', TextType::class, ['label' => 'oro.user.middle_name.label', 'required' => false])
            ->add('lastName', TextType::class, ['label' => 'oro.user.last_name.label', 'required' => true])
            ->add('nameSuffix', TextType::class, ['label' => 'oro.user.name_suffix.label', 'required' => false])
            ->add('birthday', OroBirthdayType::class, ['label' => 'oro.user.birthday.label', 'required' => false]);
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
            CheckboxType::class,
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
     * @param FormBuilderInterface $builder
     */
    protected function addOrganizationField(FormBuilderInterface $builder)
    {
        if ($this->authorizationChecker->isGranted('oro_organization_view')
            && $this->authorizationChecker->isGranted('oro_business_unit_view')
        ) {
            $builder->add(
                'organizations',
                OrganizationsSelectType::class,
                [
                    'required' => false,
                ]
            );
        }
    }
}
