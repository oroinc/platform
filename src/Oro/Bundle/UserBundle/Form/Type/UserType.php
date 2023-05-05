<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\FormBundle\Form\Type\OroBirthdayType;
use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationsSelectType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\UserSubscriber;
use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Oro\Bundle\UserBundle\Form\Provider\RolesChoicesForUserProviderInterface;
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
 * The form type for User entity.
 */
class UserType extends AbstractType
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenAccessorInterface $tokenAccessor;
    private PasswordFieldOptionsProvider $optionsProvider;
    private RolesChoicesForUserProviderInterface $choicesForUserProvider;

    private bool $isMyProfilePage;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        RequestStack $requestStack,
        PasswordFieldOptionsProvider $optionsProvider,
        RolesChoicesForUserProviderInterface $choicesForUserProvider
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->isMyProfilePage = $requestStack->getCurrentRequest()->get('_route') === 'oro_user_profile_update';
        $this->optionsProvider = $optionsProvider;
        $this->choicesForUserProvider = $choicesForUserProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addEntityFields($builder);
    }

    public function addEntityFields(FormBuilderInterface $builder): void
    {
        $builder->addEventSubscriber(new UserSubscriber($builder->getFormFactory(), $this->tokenAccessor));

        $this->setDefaultUserFields($builder);

        $attr = [];
        if ($this->isMyProfilePage) {
            $attr['readonly'] = true;
        }

        if ($this->authorizationChecker->isGranted('oro_user_role_view')) {
            $builder->add(
                'userRoles',
                EntityType::class,
                [
                    'label'         => 'oro.user.roles.label',
                    'class'         => Role::class,
                    'choice_label'  => function (Role $role) {
                        return $this->choicesForUserProvider->getChoiceLabel($role);
                    },
                    'choices'       => $this->choicesForUserProvider->getRoles(),
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
                    'class'     => Group::class,
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
                    'add_label'      => 'oro.user.emails.add',
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

    public function preSetData(FormEvent $event): void
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
                    'data-validation' => $this->optionsProvider->getDataValidationOption(),
                    'autocomplete' => 'new-password',
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
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => User::class,
            'csrf_token_id'      => 'user',
            'validation_groups'  => ['Roles', 'Default'],
            'ownership_disabled' => $this->isMyProfilePage
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_user_user';
    }

    /**
     * Set user fields
     */
    protected function setDefaultUserFields(FormBuilderInterface $builder): void
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
     */
    protected function addInviteUserField(FormBuilderInterface $builder): void
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

    protected function addOrganizationField(FormBuilderInterface $builder): void
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
