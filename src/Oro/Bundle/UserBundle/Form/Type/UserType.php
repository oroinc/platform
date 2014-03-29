<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\UserBundle\Form\EventListener\UserSubscriber;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\EmailType;

class UserType extends AbstractType
{
    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @var bool
     */
    protected $isMyProfilePage;

    /**
     * @param SecurityContextInterface $security Security context
     * @param Request                  $request  Request
     */
    public function __construct(
        SecurityContextInterface $security,
        Request $request
    ) {

        $this->security = $security;
        if ($request->attributes->get('_route') == 'oro_user_profile_update') {
            $this->isMyProfilePage = true;
        } else {
            $this->isMyProfilePage = false;
        }
    }

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
        $builder->addEventSubscriber(
            new UserSubscriber($builder->getFormFactory(), $this->security)
        );
        $this->setDefaultUserFields($builder);
        $builder
            ->add(
                'roles',
                'entity',
                array(
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
                )
            )
            ->add(
                'groups',
                'entity',
                array(
                    'label'     => 'oro.user.groups.label',
                    'class'     => 'OroUserBundle:Group',
                    'property'  => 'name',
                    'multiple'  => true,
                    'expanded'  => true,
                    'required'  => false,
                    'read_only' => $this->isMyProfilePage,
                    'disabled'  => $this->isMyProfilePage
                )
            )
            ->add(
                'businessUnits',
                'oro_business_unit_tree',
                array(
                    'multiple' => true,
                    'expanded' => true,
                    'required' => false,
                    'label'    => 'oro.user.business_units.label'
                )
            )
            ->add(
                'plainPassword',
                'repeated',
                array(
                    'label'          => 'oro.user.password.label',
                    'type'           => 'password',
                    'required'       => true,
                    'first_options'  => array('label' => 'Password'),
                    'second_options' => array('label' => 'Re-enter password'),
                )
            )
            ->add(
                'emails',
                'collection',
                array(
                    'type'           => 'oro_user_email',
                    'allow_add'      => true,
                    'allow_delete'   => true,
                    'by_reference'   => false,
                    'prototype'      => true,
                    'prototype_name' => 'tag__name__',
                    'label'          => ' '
                )
            )
            ->add('tags', 'oro_tag_select', ['label' => 'oro.tag.entity_plural_label'])
            ->add('imapConfiguration', 'oro_imap_configuration', ['label' => 'oro.user.imap_configuration.label'])
            ->add('change_password', 'oro_change_password');

        $this->addInviteUserField($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
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
                        ? array('Roles', 'Default')
                        : array('Registration', 'Roles', 'Default');
                },
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'cascade_validation'   => true
            )
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
            ->add('namePrefix', 'text', ['label' => 'oro.user.name_prefix.label', 'required' => false])
            ->add('firstName', 'text', ['label' => 'oro.user.first_name.label', 'required' => true])
            ->add('middleName', 'text', ['label' => 'oro.user.middle_name.label', 'required' => false])
            ->add('lastName', 'text', ['label' => 'oro.user.last_name.label', 'required' => true])
            ->add('nameSuffix', 'text', ['label' => 'oro.user.name_suffix.label', 'required' => false])
            ->add('birthday', 'oro_date', ['label' => 'oro.user.birthday.label', 'required' => false])
            ->add(
                'imageFile',
                'file',
                [
                    'label' => 'oro.user.image.label',
                    'required' => false,
                    'tooltip' => 'oro.user.image.tooltip',
                    'tooltip_parameters' => ['%file_size%' => $this->comparePhpIniSizes()]
                ]
            );
    }

    /**
     * Compare upload_max_filesize and post_max_size ini settings and return the lesser
     *
     * @return int
     */
    protected function comparePhpIniSizes()
    {
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');

        if ($uploadMaxFilesize <= 0) {
            return $postMaxSize;
        }

        if ($postMaxSize <= 0) {
            return $uploadMaxFilesize;
        }

        return $this->getBytes($uploadMaxFilesize) <= $this->getBytes($postMaxSize) ? $uploadMaxFilesize : $postMaxSize;
    }

    /**
     * Calculate bytes from config string
     *
     * @param string $value
     * @return int
     */
    protected function getBytes($value)
    {
        switch(substr($value, -1)) {
            case 'G':
                $value = (int)$value * 1024;
                // break intentionally omitted
            case 'M':
                $value = (int)$value * 1024;
                // break intentionally omitted
            case 'K':
                $value = (int)$value * 1024;
        }

        return $value;
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
}
