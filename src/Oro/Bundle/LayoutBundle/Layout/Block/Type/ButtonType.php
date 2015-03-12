<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class ButtonType extends AbstractContainerType
{
    const NAME = 'button';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(
                [
                    // the type of the button, can be:
                    // 'input' for <input type="button"
                    // 'button' for <button
                    // 'submit' for <input type="submit"
                    // 'submit_button' for <button type="submit"
                    // 'reset' for <input type="reset"
                    // 'reset_button' for <button type="reset"
                    'type' => 'button'
                ]
            )
            ->setOptional(['name', 'value', 'text']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['type'] = $options['type'];
        switch ($options['type']) {
            case 'button':
                $view->vars['element'] = 'button';
                break;
            case 'submit':
            case 'reset':
                $view->vars['element'] = 'input';
                if (!isset($view->vars['attr']['type'])) {
                    $view->vars['attr']['type'] = $options['type'];
                }
                break;
            case 'reset_button':
                $view->vars['element'] = 'button';
                if (!isset($view->vars['attr']['type'])) {
                    $view->vars['attr']['type'] = 'reset';
                }
                break;
            case 'submit_button':
                $view->vars['element'] = 'button';
                if (!isset($view->vars['attr']['type'])) {
                    $view->vars['attr']['type'] = 'submit';
                }
                break;
            case 'input':
                $view->vars['element'] = 'input';
                if (!isset($view->vars['attr']['type'])) {
                    $view->vars['attr']['type'] = 'button';
                }
                break;
        }

        if (isset($options['name'])) {
            $view->vars['name'] = $options['name'];
        }
        if (isset($options['value'])) {
            $view->vars['value'] = $options['value'];
        }
        if (isset($options['text'])) {
            $view->vars['text'] = $options['text'];
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
