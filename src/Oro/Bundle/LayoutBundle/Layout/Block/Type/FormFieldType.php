<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Exception\UnexpectedTypeException;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface;

class FormFieldType extends AbstractFormType
{
    const NAME = 'form_field';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver
            ->setRequired(['form_name', 'field_path'])
            ->setAllowedTypes(
                [
                    'form_name'  => 'string',
                    'field_path' => 'string'
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $formAccessor = $this->getFormAccessor($block->getContext(), $options);

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
