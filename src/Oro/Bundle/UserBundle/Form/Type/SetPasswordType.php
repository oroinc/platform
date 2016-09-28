<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\UserBundle\Form\Provider\PasswordTooltipProvider;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;

class SetPasswordType extends AbstractType
{
    /** @var PasswordTooltipProvider */
    protected $passwordTooltip;

    public function __construct(PasswordTooltipProvider $passwordTooltip)
    {
        $this->passwordTooltip = $passwordTooltip;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // build a list of indexes of the rules (1,2,3)
        $requireRules = join(',', array_keys(array_filter((array)$this->passwordTooltip->getEnabledRules())));

        $builder->add('password', 'password', [
            'required'      => true,
            'label'         => 'oro.user.new_password.label',
            'hint'          => $this->passwordTooltip->getTooltip(),
            'attr'          => [
                // config of Suggest password
                'data-require-length' => $this->passwordTooltip->getMinLength(),
                'data-require-rules' => $requireRules
            ],
            'constraints'   => [
                new NotBlank(),
                new PasswordComplexity(),
            ],
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
        return 'oro_set_password';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'compound'        => true,
            'csrf_protection' => true,
        ]);
    }
}
