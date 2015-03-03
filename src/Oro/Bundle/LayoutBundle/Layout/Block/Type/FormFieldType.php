<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Exception\UnexpectedTypeException;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface;

class FormFieldType extends AbstractType
{
    const NAME = 'form_field';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'form_name'  => null,
                'field_path' => null
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        /** @var FormAccessorInterface $formAccessor */
        $formAccessor = $block->getContext()->get($options['form_name']);
        if (!$formAccessor instanceof FormAccessorInterface) {
            throw new UnexpectedTypeException(
                $formAccessor,
                'Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface',
                sprintf('context[%s]', $options['form_name'])
            );
        }

        $view->vars['form'] = $formAccessor->getView($options['field_path']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        // prevent the form field rendering by form_rest() method,
        // if the corresponding layout block is invisible
        if ($view->vars['visible'] === false) {
            /** @var FormView $formView */
            $formView = $view->vars['form'];
            $formView->setRendered();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
