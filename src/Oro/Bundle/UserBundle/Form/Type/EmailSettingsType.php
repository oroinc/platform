<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\ImapBundle\Form\Type\ChoiceAccountType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\UserImapConfigSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Defines user email configuration/email settings form
 */
class EmailSettingsType extends AbstractType
{
    /** @var UserImapConfigSubscriber */
    protected $subscriber;

    /** @var OAuthManagerRegistry */
    protected $oauthManagerRegistry;

    public function __construct(
        UserImapConfigSubscriber $subscriber,
        OAuthManagerRegistry $oauthManagerRegistry
    ) {
        $this->subscriber = $subscriber;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'ownership_disabled' => true,
            'dynamic_fields_disabled' => true,
            'label' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);
        if ($this->oauthManagerRegistry->isOauthImapEnabled()) {
            $builder->add(
                'imapAccountType',
                ChoiceAccountType::class,
                [
                    'label' => false,
                    'constraints' => [new Valid()]
                ]
            );
        } else {
            $builder->add(
                'imapConfiguration',
                ConfigurationType::class,
                [
                    'label' => false,
                    'constraints' => [new Valid()]
                ]
            );
        }
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
        return 'oro_user_emailsettings';
    }
}
