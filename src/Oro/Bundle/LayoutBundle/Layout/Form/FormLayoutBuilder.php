<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormFieldType;

class FormLayoutBuilder implements FormLayoutBuilderInterface
{
    /** A char that separates fields within a path */
    const PATH_SEPARATOR = '.';

    /** @var array */
    protected $simpleFormTypes = [];

    /** @var FormAccessorInterface */
    protected $formAccessor;

    /** @var BlockBuilderInterface */
    protected $builder;

    /** @var array */
    protected $options;

    /** @var LayoutManipulatorInterface */
    protected $layoutManipulator;

    /** @var array */
    protected $processedFields;

    /**
     * {@inheritdoc}
     */
    public function build(FormAccessorInterface $formAccessor, BlockBuilderInterface $builder, array $options)
    {
        $this->initializeState($formAccessor, $builder, $options);
        try {
            $this->doBuild();
            $this->clearState();
        } catch (\Exception $e) {
            $this->clearState();
            throw $e;
        }
    }

    /**
     * Register form types that should be processed as a simple field even if they are compound types.
     *
     * @param string[] $formTypeNames
     */
    public function addSimpleFormTypes(array $formTypeNames)
    {
        foreach ($formTypeNames as $formTypeName) {
            $this->simpleFormTypes[$formTypeName] = true;
        }
    }

    /**
     * Builds the form layout.
     */
    protected function doBuild()
    {
        $form = $this->formAccessor->getForm();
        // add preferred fields
        foreach ($this->options['preferred_fields'] as $fieldPath) {
            $this->processForm($this->getChildForm($form, $fieldPath), $this->getParentFieldPath($fieldPath));
        }
        // add rest fields
        /** @var FormInterface $child */
        foreach ($form as $child) {
            $this->processForm($child);
        }
        $this->formAccessor->setProcessedFields($this->processedFields);
    }

    /**
     * Initializes the state of this builder.
     *
     * @param FormAccessorInterface $formAccessor
     * @param BlockBuilderInterface $builder
     * @param array                 $options
     */
    protected function initializeState(
        FormAccessorInterface $formAccessor,
        BlockBuilderInterface $builder,
        array $options
    ) {
        $this->formAccessor      = $formAccessor;
        $this->builder           = $builder;
        $this->options           = $options;
        $this->layoutManipulator = $builder->getLayoutManipulator();
        $this->processedFields   = [];
    }

    /**
     * Clears the state of this builder.
     */
    protected function clearState()
    {
        $this->formAccessor      = null;
        $this->builder           = null;
        $this->options           = null;
        $this->layoutManipulator = null;
        $this->processedFields   = null;
    }

    /**
     * Returns a form for the given field.
     *
     * @param FormInterface $form
     * @param string        $fieldPath
     *
     * @return FormInterface
     */
    protected function getChildForm(FormInterface $form, $fieldPath)
    {
        $result = $form;
        foreach (explode(self::PATH_SEPARATOR, $fieldPath) as $field) {
            $result = $result[$field];
        }

        return $result;
    }

    /**
     * Returns the layout item id for the given field.
     *
     * @param string $fieldPath
     *
     * @return string
     */
    protected function getFieldId($fieldPath)
    {
        return $this->options['form_field_prefix'] . str_replace(self::PATH_SEPARATOR, ':', $fieldPath);
    }

    /**
     * Returns the path of the parent field.
     *
     * @param string $fieldPath
     *
     * @return string|null
     */
    protected function getParentFieldPath($fieldPath)
    {
        $lastSeparator = strrpos($fieldPath, self::PATH_SEPARATOR);

        return $lastSeparator === false
            ? null
            : substr($fieldPath, 0, $lastSeparator);
    }

    /**
     * Add all fields of the given form to the layout.
     *
     * @param FormInterface $form
     * @param string        $parentFieldPath
     */
    protected function processForm(FormInterface $form, $parentFieldPath = null)
    {
        $fieldName = $form->getName();
        $fieldPath = $parentFieldPath !== null
            ? $parentFieldPath . self::PATH_SEPARATOR . $fieldName
            : $fieldName;
        if (isset($this->processedFields[$fieldPath])) {
            return;
        }

        $id = $this->getFieldId($fieldPath);
        $this->addField($fieldPath, $id);
        $this->processedFields[$fieldPath] = $id;

        if ($this->isCompoundField($form)) {
            /** @var FormInterface $child */
            foreach ($form as $child) {
                $this->processForm($child, $fieldPath);
            }
        }
    }

    /**
     * Add the given field to the layout.
     *
     * @param string      $fieldPath
     * @param string      $id
     * @param string|null $parentId
     */
    protected function addField($fieldPath, $id, $parentId = null)
    {
        $this->layoutManipulator->add(
            $id,
            $parentId ?: $this->builder->getId(),
            FormFieldType::NAME,
            [
                'form' => $this->options['form'],
                'form_name' => $this->options['form_name'],
                'field_path' => $fieldPath,
            ]
        );
    }

    /**
     * Checks whether the given form should be processed as a compound form or as a simple field.
     *
     * @param FormInterface $form
     *
     * @return bool
     */
    protected function isCompoundField(FormInterface $form)
    {
        $formConfig = $form->getConfig();
        if (!$formConfig->getCompound()) {
            return false;
        }
        $resolvedFormType = $formConfig->getType();
        while ($resolvedFormType) {
            if (isset($this->simpleFormTypes[$resolvedFormType->getInnerType()->getName()])) {
                return false;
            }
            $resolvedFormType = $resolvedFormType->getParent();
        }

        return true;
    }
}
