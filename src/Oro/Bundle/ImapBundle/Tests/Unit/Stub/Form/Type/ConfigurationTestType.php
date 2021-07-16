<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Stub\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigurationTestType extends AbstractType
{
    public const NAME = 'oro_imap_configuration_test';

    /** @var TranslatorInterface */
    protected $translator;

    /** ConfigManager */
    protected $userConfigManager;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(
        TranslatorInterface $translator,
        ConfigManager $userConfigManager,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->translator = $translator;
        $this->userConfigManager = $userConfigManager;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserEmailOrigin::class
        ]);
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('accessToken', HiddenType::class)
            ->add('refreshToken', HiddenType::class)
            ->add('accessTokenExpiresAt', HiddenType::class)
            ->add('imapHost', HiddenType::class, [
                'required' => true,
                'data' => 'imap.example.com'
            ])
            ->add('imapPort', HiddenType::class, [
                'required' => true,
                'data' => 993
            ])
            ->add('user', HiddenType::class, [
                'required' => true,
            ])
            ->add('imapEncryption', HiddenType::class, [
                'required' => true,
                'data' => 'ssl'
            ])
            ->add('clientId', HiddenType::class, [
                'data' => 'test'
            ])
            ->add('smtpHost', HiddenType::class, [
                'required' => false,
                'data' => 'smtp.example.com'
            ])
            ->add('smtpPort', HiddenType::class, [
                'required' => false,
                'data' => 465
            ])
            ->add('smtpEncryption', HiddenType::class, [
                'required'    => false,
                'data' => 'ssl'
            ])
            ->add('accountType', HiddenType::class, [
                'required'    => false
            ]);

        $builder->get('accessTokenExpiresAt')
            ->addModelTransformer(new CallbackTransformer(
                function ($originalAccessTokenExpiresAt) {
                    if ($originalAccessTokenExpiresAt === null) {
                        return '';
                    }

                    $now = new \DateTime('now', new \DateTimeZone('UTC'));
                    return $originalAccessTokenExpiresAt->format('U') - $now->format('U');
                },
                function ($submittedAccessTokenExpiresAt) {
                    if ($submittedAccessTokenExpiresAt instanceof \DateTime) {
                        return $submittedAccessTokenExpiresAt;
                    }

                    $utcTimeZone = new \DateTimeZone('UTC');
                    $newExpireDate =
                        new \DateTime('+' . (int)$submittedAccessTokenExpiresAt . ' seconds', $utcTimeZone);

                    return $newExpireDate;
                }
            ));

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var UserEmailOrigin $data */
                $data = $event->getData();
                if ($data !== null) {
                    if (($data->getOwner() === null) && ($data->getMailbox() === null)) {
                        $data->setOwner($this->tokenAccessor->getUser());
                    }
                    if ($data->getOrganization() === null) {
                        $data->setOrganization($this->tokenAccessor->getUser()->getOrganization());
                    }
                    $event->setData($data);
                }
            }
        );
    }
}
