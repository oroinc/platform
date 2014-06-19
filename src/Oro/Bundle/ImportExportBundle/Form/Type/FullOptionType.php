<?php

namespace Oro\Bundle\ImportExportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class FullOptionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!empty($options['config_id'])) {
            /** @var FieldConfigId $configId */
            $configId  = $options['config_id'];
            $fieldType = $configId->getFieldType();

            if (!$this->isSingleRelation($fieldType) && !$this->isMultipleRelation($fieldType)) {
                $view->vars['disabled'] = 'disabled';
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_importexport_full_option';
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isSingleRelation($type)
    {
        return in_array($type, array('ref-one', 'oneToOne', 'manyToOne'));
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isMultipleRelation($type)
    {
        return in_array($type, array('ref-many', 'oneToMany', 'manyToMany'));
    }
}
