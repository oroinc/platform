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
        $builder->add(
            'titles',
            LocalizedFallbackValueCollectionType::NAME,
            [
                'required' => true,
                'label' => 'oro.navigation.menuupdate.title.label',
                'options' => ['constraints' => [new NotBlank()]]
            ]
        );

        if (!empty($options['validation_groups']) && in_array('UserDefined', $options['validation_groups'])) {
            $builder->add(
                'uri',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.navigation.menuupdate.uri.label',
                ]
            );
        }

        $builder->add(
            'key',
            $options['menu_update_key'] ? 'hidden' : 'text',
            [
                'required' => true,
                'label' => 'oro.navigation.menuupdate.key.label',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MenuUpdate::class,
            'menu_update_key' => null,
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
