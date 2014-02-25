<?php

namespace Oro\Bundle\EntityBundle\Form\Guesser;

use Oro\Bundle\FormBundle\Guesser\FormBuildData;

class DoctrineTypeGuesser extends AbstractFormGuesser
{
    /**
     * @var array
     */
    protected $doctrineTypeMappings = array();

    /**
     * @param string $doctrineType
     * @param string $formType
     * @param array $formOptions
     */
    public function addDoctrineTypeMapping($doctrineType, $formType, array $formOptions = array())
    {
        $this->doctrineTypeMappings[$doctrineType] = array(
            'type' => $formType,
            'options' => $formOptions,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function guess($class, $field = null)
    {
        $metadata = $this->getMetadataForClass($class);
        if (!$metadata) {
            return null;
        }

        if ($field) {
            if ($metadata->hasAssociation($field)) {
                $targetClass = $metadata->getAssociationTargetClass($field);
                if ($metadata->isSingleValuedAssociation($field)) {
                    return $this->getFormBuildDataByEntity($targetClass);
                } elseif ($metadata->isCollectionValuedAssociation($field)) {
                    return $this->getFormBuildDataByEntity($targetClass, true);
                }
            } else {
                $fieldType = $metadata->getTypeOfField($field);
                return $this->getFormBuildDataByDoctrineType($fieldType, $class, $field);
            }
        } else {
            return $this->getFormBuildDataByEntity($class);
        }

        return null;
    }

    /**
     * @param string $doctrineType
     * @param string $class
     * @param string $field
     * @return null|FormBuildData
     */
    protected function getFormBuildDataByDoctrineType($doctrineType, $class, $field)
    {
        if (!isset($this->doctrineTypeMappings[$doctrineType])) {
            return null;
        }

        $formType = $this->doctrineTypeMappings[$doctrineType]['type'];
        $formOptions = $this->doctrineTypeMappings[$doctrineType]['options'];
        $formOptions = $this->addLabelOption($formOptions, $class, $field);

        return $this->createFormBuildData($formType, $formOptions);
    }

    /**
     * @param string $class
     * @param bool $multiple
     * @return FormBuildData
     */
    protected function getFormBuildDataByEntity($class, $multiple = false)
    {
        $formType = 'entity';
        $formOptions = array(
            'class' => $class,
            'multiple' => $multiple
        );
        $formOptions = $this->addLabelOption($formOptions, $class, null, $multiple);

        return $this->createFormBuildData($formType, $formOptions);
    }
}
