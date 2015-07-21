<?php

namespace Oro\Bundle\EmailBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\EmailBundle\Form\Type\Filter\ChoiceOriginFolderFilterType;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class ChoiceOriginFolderFilter extends ChoiceFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return ChoiceOriginFolderFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $formView  = $this->getForm()->createView();
        $fieldView = $formView->children['value'];
        $choices = $fieldView->vars['choices'];

        $metadata = $this->getDefaultMetadata();
        $metadata['choices']         = $choices;
        $metadata['populateDefault'] = $formView->vars['populate_default'];
        if (!empty($formView->vars['default_value'])) {
            $metadata['placeholder'] = $formView->vars['default_value'];
        }
        if (!empty($formView->vars['null_value'])) {
            $metadata['nullValue'] = $formView->vars['null_value'];
        }

        if ($fieldView->vars['multiple']) {
            $metadata[FilterUtility::TYPE_KEY] = 'multiselect-originfolder';
        }

        return $metadata;
    }

    /**
     * @return array
     */
    protected function getDefaultMetadata()
    {
        $formView = $this->getForm()->createView();
        $typeView = $formView->children['type'];

        $defaultMetadata = [
            'name'                     => $this->getName(),
            // use filter name if label not set
            'label'                    => ucfirst($this->name),
            'choices'                  => $typeView->vars['choices']
        ];

        $metadata = array_diff_key(
            $this->get() ?: [],
            array_flip($this->util->getExcludeParams())
        );
        $metadata = $this->mapParams($metadata);
        $metadata = array_merge($defaultMetadata, $metadata);

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($options['populate_default'])) {
            $view->vars['populate_default'] = $options['populate_default'];
            $view->vars['default_value']    = $options['default_value'];
        }
        if (!empty($options['null_value'])) {
            $view->vars['null_value'] = $options['null_value'];
        }
    }
}
