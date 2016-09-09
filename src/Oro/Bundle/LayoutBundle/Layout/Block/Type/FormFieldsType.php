<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options as LayoutOptions;

use Oro\Bundle\LayoutBundle\Layout\Form\ConfigurableFormAccessorInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormLayoutBuilderInterface;

/**
 * This block type is responsible to build the layout for a Symfony's form object.
 * Naming convention:
 *  field id = $options['form_field_prefix'] + field path (path separator is replaced with colon (:))
 *      for example: form_firstName or form_address:city  where 'form_' is the prefix
 *  group id = $options['form_group_prefix'] + group name
 *      for example: form:group_myGroup where 'form:group_' is the prefix
 */
class FormFieldsType extends AbstractFormType
{
    const NAME = 'form_fields';

    /** @var FormLayoutBuilderInterface */
    protected $formLayoutBuilder;

    /**
     * @param FormLayoutBuilderInterface $formLayoutBuilder
     */
    public function __construct(FormLayoutBuilderInterface $formLayoutBuilder)
    {
        $this->formLayoutBuilder = $formLayoutBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                // example: ['jobTitle', 'user.lastName']
                'preferred_fields'  => [],
                // example:
                // [
                //   'general'    => [
                //     'title'  => 'General Info',
                //     'fields' => ['user.firstName', 'user.lastName']
                //   ],
                //   'additional'    => [
                //     'title'   => 'Additional Info',
                //     'default' => true
                //   ]
                // ]
                'groups'            => [],
                'form_prefix'       => function (Options $options, $value) {
                    return null === $value ? $options['form_name'] : $value;
                },
                'form_field_prefix' => function (Options $options, $value) {
                    return null === $value ? $options['form_prefix'] . '_' : $value;
                },
                'form_group_prefix' => function (Options $options, $value) {
                    return null === $value ? $options['form_prefix'] . ':group_' : $value;
                },
                'split_to_fields'   => false
            ]
        );
        $resolver->setDefined(['form_data']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, LayoutOptions $options)
    {
        if ($options['split_to_fields']) {
            $formAccessor = $this->getFormAccessor($builder->getContext(), $options);
            $this->formLayoutBuilder->build($formAccessor, $builder, $options);
        }
    }

    public function buildView(BlockView $view, BlockInterface $block, LayoutOptions $options)
    {
        $view->vars['form'] = $options->getOr('form');
        $view->vars['form_name'] = $options->getOr('form_name');
        $view->vars['form_data'] = $options->getOr('form_data');
        $view->vars['split_to_fields'] = $options->getOr('split_to_fields');
        parent::buildView($view, $block, $options);

    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block)
    {
        $formAccessor = $this->getFormAccessor($block->getContext(), $view->vars);
        if ($formAccessor instanceof ConfigurableFormAccessorInterface) {
            $formAccessor->setFormData($view->vars['form_data']);
        }
        $formView = $formAccessor->getView();
        if (!isset($view->vars['class_prefix'])) {
            $view->vars['class_prefix'] = $block->getId();
        }
        $this->setClassPrefixToFormView($formView, $view->vars['class_prefix']);
        $view->vars['form'] = $formView;

        if (!$view->vars['split_to_fields']) {
            return;
        }
        $formAccessor = $this->getFormAccessor($block->getContext(), $view->vars);

        // prevent form fields rendering by form_rest() method,
        // if the corresponding layout block has been removed
        foreach ($formAccessor->getProcessedFields() as $formFieldPath => $blockId) {
            if (isset($view[$blockId])) {
                $this->checkExistingFieldView($view, $view[$blockId], $formFieldPath);
                continue;
            }
            if (isset($view->blocks[$blockId])) {
                $this->checkExistingFieldView($view, $view->blocks[$blockId], $formFieldPath);
                continue;
            }

            $this->getFormFieldView($view, $formFieldPath)->setRendered();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ContainerType::NAME;
    }

    /**
     * Returns form field view
     *
     * @param BlockView $view
     * @param string    $formFieldPath
     *
     * @return FormView
     */
    protected function getFormFieldView(BlockView $view, $formFieldPath)
    {
        /** @var FormView $form */
        $form = $view->vars['form'];
        foreach (explode('.', $formFieldPath) as $field) {
            $form = $form[$field];
        }

        return $form;
    }

    /**
     * Checks whether an existing field view is the view created in buildBlock method,
     * and if it is another view mark the corresponding form field as rendered
     *
     * @param BlockView $view
     * @param BlockView $childView
     * @param string    $formFieldPath
     */
    protected function checkExistingFieldView(BlockView $view, BlockView $childView, $formFieldPath)
    {
        if (!isset($childView->vars['form'])) {
            $this->getFormFieldView($view, $formFieldPath)->setRendered();
        } else {
            $formFieldView = $this->getFormFieldView($view, $formFieldPath);
            if ($childView->vars['form'] !== $formFieldView) {
                $formFieldView->setRendered();
            }
        }
    }

    /**
     * Sets class_prefix to FormView and it's childs recursively
     *
     * @param FormView $formView
     * @param string   $classPrefix
     */
    protected function setClassPrefixToFormView(FormView $formView, $classPrefix)
    {
        $formView->vars['class_prefix'] = $classPrefix;

        if (empty($formView->children) && !isset($formView->vars['prototype'])) {
            return;
        }
        foreach ($formView->children as $child) {
            $this->setClassPrefixToFormView($child, $classPrefix);
        }
        if (isset($formView->vars['prototype'])) {
            $this->setClassPrefixToFormView($formView->vars['prototype'], $classPrefix);
        }
    }
}
