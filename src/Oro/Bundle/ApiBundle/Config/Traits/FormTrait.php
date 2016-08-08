<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * @property array $items
 */
trait FormTrait
{
    /**
     * Gets the form type.
     *
     * @return string|null
     */
    public function getFormType()
    {
        return array_key_exists(EntityDefinitionConfig::FORM_TYPE, $this->items)
            ? $this->items[EntityDefinitionConfig::FORM_TYPE]
            : null;
    }

    /**
     * Sets the form type.
     *
     * @param string|null $formType
     */
    public function setFormType($formType)
    {
        if ($formType) {
            $this->items[EntityDefinitionConfig::FORM_TYPE] = $formType;
        } else {
            unset($this->items[EntityDefinitionConfig::FORM_TYPE]);
        }
    }

    /**
     * Gets the form options.
     *
     * @return array|null
     */
    public function getFormOptions()
    {
        return array_key_exists(EntityDefinitionConfig::FORM_OPTIONS, $this->items)
            ? $this->items[EntityDefinitionConfig::FORM_OPTIONS]
            : null;
    }

    /**
     * Sets the form options.
     *
     * @param array|null $formOptions
     */
    public function setFormOptions($formOptions)
    {
        if ($formOptions) {
            $this->items[EntityDefinitionConfig::FORM_OPTIONS] = $formOptions;
        } else {
            unset($this->items[EntityDefinitionConfig::FORM_OPTIONS]);
        }
    }

    /**
     * Adds a validation constraint to the form options.
     *
     * @param Constraint $constraint
     */
    public function addFormConstraint(Constraint $constraint)
    {
        $entityOptions = $this->getFormOptions();
        $entityOptions['constraints'][] = $constraint;
        $this->setFormOptions($entityOptions);
    }
}
