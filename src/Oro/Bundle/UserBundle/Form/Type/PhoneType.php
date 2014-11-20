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
            'phones',
            'oro_phone_collection',
            array(
                'label'    => 'oro.user.phones.label',
                'type'     => 'oro_phone',
                'required' => false/*, hmm. need to look at this file to see wht to do here
                'options'  => array('data_class' => 'OroCRM\Bundle\ContactBundle\Entity\ContactPhone'*/)
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
