<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Select2 region form type with AJAX support
 */
class RegionType extends AbstractType
{
    public const COUNTRY_OPTION_KEY = 'country_field';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->setAttribute(self::COUNTRY_OPTION_KEY, $options[self::COUNTRY_OPTION_KEY]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = function (Options $options) {
            // show empty list if country is not selected
            if (empty($options['country'])) {
                return array();
            }

            return null;
        };

        $resolver
            ->setDefaults(
                array(
                    'label'         => 'oro.address.region.entity_label',
                    'class'         => Region::class,
                    'random_id'     => true,
                    'choice_label'  => 'name',
                    'query_builder' => null,
                    'choices'       => $choices,
                    'country'       => null,
                    'country_field' => null,
                    'configs' => array(
                        'placeholder' => 'oro.address.form.choose_region',
                        'allowClear' => true
                    ),
                    'placeholder' => '',
                    'empty_data'  => null
                )
            );
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['country_field'] = $form->getConfig()->getAttribute(self::COUNTRY_OPTION_KEY);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2TranslatableEntityType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_region';
    }
}
