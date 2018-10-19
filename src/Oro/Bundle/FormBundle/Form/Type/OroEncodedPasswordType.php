<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This class provides ability to encrypt/decrypt form fields
 */
class OroEncodedPasswordType extends AbstractType
{
    /** @var SymmetricCrypterInterface */
    protected $encryptor;

    /**
     * @param SymmetricCrypterInterface $encryptor
     */
    public function __construct(SymmetricCrypterInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->getEncryptClosure($options));
    }

    /**
     * @param array $options
     *
     * @return callable
     */
    protected function getEncryptClosure($options)
    {
        $enc = $this->encryptor;
        $isEncode = !empty($options['encode']) && $options['encode'];

        return function (FormEvent $event) use ($enc, $isEncode) {
            $form = $event->getForm();
            $password = $event->getData();
            $oldPassword = $form->getData();

            if (empty($password) && $oldPassword) {
                // populate old password
                $password = $oldPassword;
            } elseif (!empty($password) && $isEncode) {
                $password = $this->encryptor->encryptData($password);
            }

            $event->setData($password);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (false === $options['browser_autocomplete']) {
            // Use soft merge ("+" operator) to preserve 'autocomplete' value if it was already specified.
            $view->vars['attr'] += ['autocomplete' => 'new-password'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'encode' => true,
            'browser_autocomplete' => false,
        ]);

        $resolver->setAllowedTypes('encode', 'bool');
        $resolver->setAllowedTypes('browser_autocomplete', 'bool');
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return PasswordType::class;
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
        return 'oro_encoded_password';
    }
}
