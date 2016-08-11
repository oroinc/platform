<?php

namespace Oro\Bundle\ActivityBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\MultipleAssociationChoiceType as BaseMultipleAssociationChoiceType;

class MultipleAssociationChoiceType extends BaseMultipleAssociationChoiceType
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
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
        return 'oro_activity_multiple_association_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_entity_extend_multiple_association_choice';
    }
}
