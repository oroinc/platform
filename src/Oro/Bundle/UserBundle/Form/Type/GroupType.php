<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * Roles was commended due a task BAP-1675
         */
        $builder
            ->add(
                'name',
                TextType::class,
                array(
                    'label' => 'oro.user.group.name.label',
                    'required' => true,
                )
            )
            /*->add(
                'roles',
                'entity',
                array(
                    'label'    => 'Roles',
                    'class'    => 'OroUserBundle:Role',
                    'query_builder' => function ($builder) {
                        return $builder->createQueryBuilder('r')
                            ->where('r.role != :anonRole')
                            ->setParameter('anonRole', User::ROLE_ANONYMOUS);
                    },
                    'choice_label' => 'label',
                    'required' => true,
                    'multiple' => true,
                )
            )*/
            ->add(
                'appendUsers',
                EntityIdentifierType::class,
                array(
                    'class'    => 'OroUserBundle:User',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                )
            )
            ->add(
                'removeUsers',
                EntityIdentifierType::class,
                array(
                    'class'    => 'OroUserBundle:User',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\UserBundle\Entity\Group',
                'csrf_token_id' => 'group',
            )
        );
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
        return 'oro_user_group';
    }
}
