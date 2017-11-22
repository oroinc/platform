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
        if (!array_key_exists(EntityDefinitionConfig::FORM_TYPE, $this->items)) {
            return null;
        }

        return $this->items[EntityDefinitionConfig::FORM_TYPE];
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
        if (!array_key_exists(EntityDefinitionConfig::FORM_OPTIONS, $this->items)) {
            return null;
        }

        return $this->items[EntityDefinitionConfig::FORM_OPTIONS];
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
     * Sets a form option. If an option is already exist its value will be replaced with new value.
     *
     * @param string $name  The name of an option
     * @param mixed  $value The value of an option
     */
    public function setFormOption($name, $value)
    {
        $entityOptions = $this->getFormOptions();
        $entityOptions[$name] = $value;
        $this->setFormOptions($entityOptions);
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
