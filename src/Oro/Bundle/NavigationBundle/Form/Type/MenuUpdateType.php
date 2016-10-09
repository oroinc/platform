<?php

namespace Oro\Bundle\NavigationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
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
                'options' => ['constraints' => [new NotBlank()]],
                'validation_groups' => ['Default'],
            ]
        );

        if (!$options['exists_in_navigation_yml']) {
            $builder->add(
                'uri',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.navigation.menuupdate.uri.label',
                    'validation_groups' => ['UserDefined'],
                ]
            );
        }

        if (!empty($options['acl_resource_id'])) {
            $builder->add(
                'aclResourceId',
                'text',
                [
                    'label' => 'oro.navigation.menuupdate.acl_resource_id.label',
                    'mapped' => false,
                    'disabled' => true,
                    'data' => $options['acl_resource_id'],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MenuUpdate::class,
            'exists_in_navigation_yml' => true,
            'acl_resource_id' => null,
            'validation_groups' => function (FormInterface $form) {
                $groups = ['Default'];
                if (!$form->getConfig()->getOption('exists_in_navigation_yml')) {
                    $groups = ['UserDefined'];
                }
                return $groups;
            }
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
