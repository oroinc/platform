<?php

namespace Oro\Bundle\ScopeBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Scope collection type.
 */
class ScopeCollectionType extends AbstractType
{
    const NAME = 'oro_scope_collection';

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function ($event) {
            $collectionForm = $event->getForm();
            $constraints = $collectionForm->getConfig()->getOption('scope_constraints', []);
            foreach ($collectionForm as $index => $scopeForm) {
                FormUtils::mergeFieldOptionsRecursive($collectionForm, $index, ['constraints' => $constraints]);
            }
        }, -255);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => ScopeType::class,
                'error_bubbling' => false,
                'handle_primary' => false,
                'scope_constraints' => [],
            ]
        );
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
