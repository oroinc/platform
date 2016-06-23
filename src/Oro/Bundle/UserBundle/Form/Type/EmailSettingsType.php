<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class EmailSettingsType extends AbstractType
{
    /** ConfigManager */
    protected $userConfigManager;

    /**
     * @param ConfigManager            $userConfigManager
     */
    public function __construct(
        ConfigManager            $userConfigManager
    ) {
        $this->userConfigManager = $userConfigManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'         => 'Oro\Bundle\UserBundle\Entity\User',
            'cascade_validation' => true,
            'ownership_disabled' => true,
            'dynamic_fields_disabled' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->userConfigManager->get('oro_imap.enable_google_imap')) {
            $builder->add(
                'imapAccountType',
                'oro_imap_choice_account_type',
                ['label' => false]
            );
        } else {
            $builder->add(
                'imapConfiguration',
                'oro_imap_configuration',
                ['label' => false]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_user_emailsettings';
    }
}
