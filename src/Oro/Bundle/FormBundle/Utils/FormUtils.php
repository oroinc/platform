<?php

namespace Oro\Bundle\FormBundle\Utils;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\DataTransformerInterface;

class FormUtils
{
    /**
     * Replace form field by the same field with different options
     * Example of usage:
     *    - need to disable some field
     *      FormUtils::replaceField($form, 'fieldName', ['disabled' => true])
     *
     * @param FormInterface $form
     * @param string        $fieldName
     * @param array         $modifyOptions
     * @param array         $unsetOptions ['optionName' ...]
     */
    public static function replaceField(
        FormInterface $form,
        $fieldName,
        array $modifyOptions = [],
        array $unsetOptions = []
    ) {
        $config  = $form->get($fieldName)->getConfig();
        $options = $config->getOptions();

        if (array_key_exists('auto_initialize', $options)) {
            $options['auto_initialize'] = false;
        }
        $options = array_merge($options, $modifyOptions);
        $options = array_diff_key($options, array_flip($unsetOptions));
        $form->add($fieldName, $config->getType()->getName(), $options);
    }

    /**
     * Appends CSS class(es) to given form view
     *
     * @param FormView $view
     * @param string|[]   $cssClass
     */
    public static function appendClass(FormView $view, $cssClasses)
    {
        $vars       = $view->vars;
        $cssClasses = is_array($cssClasses) ? $cssClasses : [$cssClasses];

        $vars['attr']          = isset($vars['attr']) ? $vars['attr'] : [];
        $vars['attr']['class'] = isset($vars['attr']['class']) ? $vars['attr']['class'] : '';

        $vars['attr']['class'] = trim(implode(' ', array_merge([$vars['attr']['class']], $cssClasses)));

        $view->vars = $vars;
    }

    /**
     * Replace transformer in form builder, keep sorting of transformers
     *
     * @param FormBuilderInterface     $builder
     * @param DataTransformerInterface $transformerToReplace
     * @param string                   $type               Model or View transformer type to replace in
     * @param callable                 $comparisonCallback Callable function that will be
     *                                                     used for old transformer detection
     */
    public static function replaceTransformer(
        FormBuilderInterface $builder,
        DataTransformerInterface $transformerToReplace,
        $type = 'model',
        callable $comparisonCallback = null
    ) {
        $transformers    = 'model' === $type ? $builder->getModelTransformers() : $builder->getViewTransformers();
        $newTransformers = [];

        $hasCallback = null !== $comparisonCallback;
        $class       = get_class($transformerToReplace);
        foreach ($transformers as $key => $transformer) {
            if (($hasCallback && call_user_func($comparisonCallback, $transformer, $key))
                || (!$hasCallback && is_a($transformer, $class))
            ) {
                $newTransformers[] = $transformerToReplace;
            } else {
                $newTransformers[] = $transformer;
            }
        }

        if (!in_array($transformerToReplace, $newTransformers, true)) {
            $newTransformers[] = $transformerToReplace;
        }

        if ('model' === $type) {
            $builder->resetModelTransformers();
            array_walk($newTransformers, [$builder, 'addModelTransformer']);
        } else {
            $builder->resetViewTransformers();
            array_walk($newTransformers, [$builder, 'addViewTransformer']);
        }
    }
}
