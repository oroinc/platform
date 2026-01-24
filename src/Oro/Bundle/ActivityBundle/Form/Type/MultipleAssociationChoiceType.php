<?php

namespace Oro\Bundle\ActivityBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\MultipleAssociationChoiceType as BaseMultipleAssociationChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type for selecting multiple activity associations with activity-specific constraints.
 *
 * This form type extends the base multiple association choice type to add activity-specific
 * validation logic. It prevents users from selecting an activity type that matches the target
 * entity's class, as an entity cannot be associated with an activity of the same type.
 * This ensures data integrity and prevents invalid activity associations in the UI.
 */
class MultipleAssociationChoiceType extends BaseMultipleAssociationChoiceType
{
    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        /** @var EntityConfigId $configId */
        $configId  = $options['config_id'];
        $targetClassName = $configId->getClassName();

        /** @var FormView $choiceView */
        foreach ($view->children as $choiceView) {
            // disable activity with same class as target entity
            if ((isset($view->vars['disabled']) && $view->vars['disabled'])
                || ($choiceView->vars['value'] === $targetClassName)
            ) {
                $choiceView->vars['disabled'] = true;
            }
        }
    }

    #[\Override]
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_activity_multiple_association_choice';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return BaseMultipleAssociationChoiceType::class;
    }
}
