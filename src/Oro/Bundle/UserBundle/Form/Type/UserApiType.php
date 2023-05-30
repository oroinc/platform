<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Oro\Bundle\UserBundle\Form\EventListener\UserApiSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for User entity for API calls.
 */
class UserApiType extends UserType
{
    /** ConfigManager */
    protected $userConfigManager;

    public function setUserConfigManager(ConfigManager $userConfigManager)
    {
        $this->userConfigManager = $userConfigManager;
    }

    /**
     * {@inheritdoc}
     */
    public function addEntityFields(FormBuilderInterface $builder)
    {
        parent::addEntityFields($builder);
        /**
         * For API, imap configuration is updated from user form
         * with imap config subform without buttons
         */
        if ($this->userConfigManager && !$this->userConfigManager->get('oro_imap.enable_google_imap')) {
            $builder->add(
                'imapConfiguration',
                ConfigurationType::class,
                ['add_check_button' => false]
            );
        }
        $builder
            ->addEventSubscriber(new UserApiSubscriber($builder->getFormFactory()))
            ->addEventSubscriber(new PatchSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'csrf_protection'   => false,
            'validation_groups' => function ($form) {
                if ($form instanceof FormInterface) {
                    $user = $form->getData();
                } elseif ($form instanceof FormView) {
                    $user = $form->vars['value'];
                } else {
                    $user = null;
                }

                return $user && $user->getId()
                    ? ['ProfileAPI', 'UserForm', 'Default']
                    : ['Registration', 'ProfileAPI', 'UserForm', 'Default'];
            },
        ]);
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
        return 'user';
    }

    /**
     * Add Invite user fields
     */
    protected function addInviteUserField(FormBuilderInterface $builder)
    {
    }
}
