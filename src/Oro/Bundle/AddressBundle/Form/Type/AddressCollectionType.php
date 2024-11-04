<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 *  Addresses collection form type
 */
class AddressCollectionType extends AbstractType
{
    const NAME = 'oro_address_collection';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer(
            'entry_options',
            function (Options $options, $values) {
                if (!$values) {
                    $values = [];
                }
                $values['single_form'] = false;

                return $values;
            }
        );
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['validation_ignore_if_not_changed'] = true;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
