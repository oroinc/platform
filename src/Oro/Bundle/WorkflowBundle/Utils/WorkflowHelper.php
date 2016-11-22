<?php

namespace Oro\Bundle\WorkflowBundle\Utils;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;

class WorkflowHelper
{
    /**
     * @param FormInterface $form
     * @return string[]
     * @throws UnknownAttributeException
     * @throws WorkflowException
     * @throws RuntimeException
     */
    public static function getRealWorkflowData(FormInterface $form)
    {
        $options = $form->getRoot()->getConfig()->getOptions();

        if (empty($options['workflow'])) {
            throw new WorkflowException('Workflow bot found');
        }

        /** @var Workflow $workflow */
        $workflow = $options['workflow'];

        $attributeManager = $workflow->getAttributeManager();

        $fieldAttribute = $attributeManager->getAttribute($form->getName());

        if (!$fieldAttribute) {
            throw new UnknownAttributeException(sprintf("Attribute for %s not found", $form->getName()));
        }

        $propertyPath = $fieldAttribute->getPropertyPath();

        $propertyPathData = explode('.', $propertyPath);

        if (empty($propertyPathData)) {
            throw new NoSuchPropertyException(sprintf("Property path for %s not found", $form->getName()));
        }

        $fieldName = array_pop($propertyPathData);
        $dataClassAlias = array_pop($propertyPathData);

        $dataClassAttribute = $attributeManager->getAttribute($dataClassAlias);

        $dataClass = $dataClassAttribute->getOption('class');

        if (!$dataClass) {
            throw new \RuntimeException(sprintf("Data class for %s attribute not found", $dataClassAttribute));
        }

        return [
            'fieldName' => $fieldName,
            'dataClass'  => $dataClass
        ];
    }
}
