<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

use Symfony\Component\Form\FormView;

abstract class AbstractFormAccessor implements FormAccessorInterface
{
    /** @var FormView */
    private $formView;

    /** @var array */
    private $processedFields;

    /**
     * {@inheritdoc}
     */
    public function getView($fieldPath = null)
    {
        $result = $this->getFormView();
        if ($fieldPath !== null) {
            foreach (explode('.', $fieldPath) as $field) {
                $result = $result[$field];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessedFields()
    {
        return $this->processedFields;
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessedFields($processedFields)
    {
        $this->processedFields = $processedFields;
    }

    /**
     * @return FormView
     */
    protected function getFormView()
    {
        if (!$this->formView) {
            $this->formView = $this->getForm()->createView();
        }

        return $this->formView;
    }
}
