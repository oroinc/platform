<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\UserBundle\Entity\UserApi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserApiKeyGenType extends AbstractType
{
    const NAME = 'oro_user_apikey_gen';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('apiKey', UserApiKeyGenKeyType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('apiKeyElementId');
        $resolver->setAllowedTypes('apiKeyElementId', ['string']);
        $resolver->setDefaults(
            [
                'data_class' => UserApi::class,
                'csrf_protection' => ['enabled' => true, 'fieild_name' => 'apikey_token'],
                'csrf_token_id' => self::NAME,
                'apiKeyElementId' => 'user-apikey-gen-elem'
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['apiKeyElementId'] = $options['apiKeyElementId'];
    }
}
