<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Form\Type\ChoiceAccountType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Manager\OAuth2ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\UserImapConfigSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Defines user email configuration/email settings form
 */
class EmailSettingsType extends AbstractType
{
    /** @var ConfigManager */
    protected $userConfigManager;

    /** @var UserImapConfigSubscriber */
    protected $subscriber;

    /** @var OAuth2ManagerRegistry */
    protected $oauthManagerRegistry;

    /**
     * @param ConfigManager $userConfigManager
     * @param UserImapConfigSubscriber $subscriber
     */
    public function __construct(
        ConfigManager            $userConfigManager,
        UserImapConfigSubscriber $subscriber
    ) {
        $this->userConfigManager = $userConfigManager;
        $this->subscriber = $subscriber;
    }

    /**
     * @param OAuth2ManagerRegistry $oauthManagerRegistry
     */
    public function setOAuth2ManagerRegistry(OAuth2ManagerRegistry $oauthManagerRegistry)
    {
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'         => 'Oro\Bundle\UserBundle\Entity\User',
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
            $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->getUserOriginListener());
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
     * Returns callable for checking if exisitng origin method was disabled.
     * Related form remains then, though with non-active CTAs
     *
     * @return callable
     */
    protected function getUserOriginListener(): callable
    {
        return function (FormEvent $event) {
            $form = $event->getForm();
            /** @var User|null $user */
            $user = $event->getData();
            if (null === $user || !($user instanceof User)) {
                return;
            }
            if ($this->isApplicableAccountType($user)) {
                $form->remove('imapConfiguration');
                if (!$form->has('imapAccountType')) {
                    $form->add(
                        'imapAccountType',
                        ChoiceAccountType::class,
                        [
                            'label' => false,
                            'constraints' => [new Valid()]
                        ]
                    );
                }
            }
        };
    }

    /**
     * Provides dropdown for OAUth account types and
     * cleanup calls - non-persisted user
     *
     * @param User $user
     * @return bool
     */
    private function isApplicableAccountType(User $user): bool
    {
        if (!$user->getId()) {
            $origin = $user->getImapConfiguration();
            return !$origin || !$origin->getPassword();
        }
        $origin = $user->getImapConfiguration();

        return (null !== $origin) && $origin->getAccountType() !== AccountTypeModel::ACCOUNT_TYPE_OTHER;
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
