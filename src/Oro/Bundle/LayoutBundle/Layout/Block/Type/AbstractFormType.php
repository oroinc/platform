<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Exception\UnexpectedTypeException;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface;

abstract class AbstractFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(
                [
                    'form' => null,
                    'form_name' => 'form',
                ]
            )
            ->setAllowedTypes(['form_name' => 'string']);
    }

    /**
     * Returns the form accessor.
     *
     * @param ContextInterface $context
     * @param array            $options
     *
     * @return FormAccessorInterface
     *
     * @throws \OutOfBoundsException if the context does not contain the form accessor
     * @throws UnexpectedTypeException if the form accessor stored in the context has invalid type
     */
    protected function getFormAccessor(ContextInterface $context, array $options)
    {
        /** @var FormAccessorInterface $formAccessor */
        if (isset($options['form'])) {
            $formAccessor = $options['form'];
            if (!$formAccessor instanceof FormAccessorInterface) {
                throw new UnexpectedTypeException(
                    $formAccessor,
                    'Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface',
                    'options[form]'
                );
            }
        } else {
            $formAccessor = $context->get($options['form_name']);
            if (!$formAccessor instanceof FormAccessorInterface) {
                throw new UnexpectedTypeException(
                    $formAccessor,
                    'Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface',
                    sprintf('context[%s]', $options['form_name'])
                );
            }
        }

        return $formAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        if (isset($options['label']) && isset($view->vars['form'])) {
            $view->vars['form']->vars['label'] = $options['label'];
        }
    }
}
