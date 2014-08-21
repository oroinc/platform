<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AssociationChoiceType extends AbstractAssociationType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(
            [
                'empty_value' => false,
                'choices'     => ['No', 'Yes']
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function isSchemaUpdateRequired($newVal, $oldVal)
    {
        return true == $newVal && false == $oldVal;
    }

    /**
     * {@inheritdoc}
     */
    protected function disableView(FormView $view)
    {
        parent::disableView($view);
        $this->appendClassAttr($view->vars, 'disabled-choice');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_association_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
