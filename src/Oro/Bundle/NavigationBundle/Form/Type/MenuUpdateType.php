<?php

namespace Oro\Bundle\NavigationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;

class MenuUpdateType extends AbstractType
{
    const NAME = 'oro_navigation_menu_update';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isCreate = is_array($options['validation_groups']) &&
            in_array('Create', $options['validation_groups']);

        $builder
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'required' => $isCreate,
                    'label' => 'oro.navigation.menuupdate.title.label',
                    'options' => $isCreate ? ['constraints' => [new NotBlank()]] : []
                ]
            )
            ->add(
                'uri',
                'text',
                [
                    'required' => $isCreate,
                    'label' => 'oro.navigation.menuupdate.uri.label',
                    'validation_groups' => $isCreate ? ['Create'] : false,
                ]
            )
            ->add(
                'active'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'create_new' => false,
            'data_class' => MenuUpdate::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
