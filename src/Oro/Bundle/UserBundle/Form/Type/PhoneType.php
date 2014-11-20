<?php
namespace Oro\Bundle\UserBundle\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
class PhoneType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'phone',
            'phone',
            array(
                'label' => 'oro.user.phone.label',
                'required' => false,
            )
        );
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_user_phone';
    }
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                 'data_class' => 'Oro\Bundle\UserBundle\Entity\Phone',
            )
        );
    }
}
