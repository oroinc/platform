<?php

namespace Oro\Bundle\TagBundle\Filter;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Form\Type\EntityTagsFilterType;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class EntityTagsFilter extends AbstractTagsFilter
{
    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = EntityTagsFilterType::NAME;
        if (isset($params['null_value'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['null_value'] = $params['null_value'];
        }
        $this->name   = $name;
        $this->params = $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        if (!$this->form) {
            $this->form = $this->formFactory->create(
                $this->getFormType(),
                null
            );
        }

        return $this->form;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return EntityTagsFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $formView  = $this->getForm()->createView();
        $fieldView = $formView->children['value'];

        $defaultMetadata = [
            'name'         => $this->getName(),
            'translatable' => true,
            'enabled'      => true,
            'choices'      => $fieldView->vars['choices'],
            'lazy'         => $this->isLazy(),
        ];

        $metadata         = array_diff_key(
            $this->get() ?: [],
            array_flip($this->util->getExcludeParams())
        );
        $metadata         = $this->mapParams($metadata);
        $metadata['type'] = 'multichoice';

        $metadata = array_merge($defaultMetadata, $metadata);

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseData($data)
    {
        $data = parent::parseData($data);

        if (false !== $data) {
            $data['value']   = array_map(
                function (Tag $tag) {
                    return $tag->getId();
                },
                $data['value']
            );
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return $this->get('options')['field_options']['entity_class'];
    }
}
