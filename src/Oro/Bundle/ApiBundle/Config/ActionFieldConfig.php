<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Validator\Constraint;

/**
 * Represents a field configuration inside "actions" section.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ActionFieldConfig implements FieldConfigInterface
{
    /** @var bool|null */
    protected $exclude;

    /** @var array */
    protected $items = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = ConfigUtil::convertItemsToArray($this->items);
        if (true === $this->exclude) {
            $result[ConfigUtil::EXCLUDE] = $this->exclude;
        }

        return $result;
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->items = ConfigUtil::cloneItems($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $defaultValue = null)
    {
        if (!\array_key_exists($key, $this->items)) {
            return $defaultValue;
        }

        return $this->items[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        if (null !== $value) {
            $this->items[$key] = $value;
        } else {
            unset($this->items[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->items[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return \array_keys($this->items);
    }

    /**
     * Indicates whether the exclusion flag is set explicitly.
     *
     * @return bool
     */
    public function hasExcluded()
    {
        return null !== $this->exclude;
    }

    /**
     * Indicates whether the exclusion flag.
     *
     * @return bool
     */
    public function isExcluded()
    {
        if (null === $this->exclude) {
            return false;
        }

        return $this->exclude;
    }

    /**
     * Sets the exclusion flag.
     *
     * @param bool|null $exclude The exclude flag or NULL to remove this option
     */
    public function setExcluded($exclude = true)
    {
        $this->exclude = $exclude;
    }

    /**
     * Indicates whether the direction option is set explicitly.
     * If this option is not set, both the request and the response can contain this field.
     *
     * @return bool
     */
    public function hasDirection()
    {
        return $this->has(ConfigUtil::DIRECTION);
    }

    /**
     * Sets a value that indicates whether the field is input-only, output-only or bidirectional.
     *
     * * The "input-only" means that the request data can contain this field,
     *   but the response data cannot.
     * * The "output-only" means that the response data can contain this field,
     *   but the request data cannot.
     * * The "bidirectional" means that both the request data and the response data can contain this field.
     *
     * The "bidirectional" is the default behaviour.
     *
     * @param string|null $direction Can be "input-only", "output-only", "bidirectional"
     *                               or NULL to remove this option and use default behaviour for it
     */
    public function setDirection($direction)
    {
        if ($direction) {
            if (ConfigUtil::DIRECTION_INPUT_ONLY !== $direction
                && ConfigUtil::DIRECTION_OUTPUT_ONLY !== $direction
                && ConfigUtil::DIRECTION_BIDIRECTIONAL !== $direction
            ) {
                throw new \InvalidArgumentException(\sprintf(
                    'The possible values for the direction are "%s", "%s" or "%s".',
                    ConfigUtil::DIRECTION_INPUT_ONLY,
                    ConfigUtil::DIRECTION_OUTPUT_ONLY,
                    ConfigUtil::DIRECTION_BIDIRECTIONAL
                ));
            }
            $this->items[ConfigUtil::DIRECTION] = $direction;
        } else {
            unset($this->items[ConfigUtil::DIRECTION]);
        }
    }

    /**
     * Indicates whether the request data can contain this field.
     *
     * @return bool
     */
    public function isInput()
    {
        if (!\array_key_exists(ConfigUtil::DIRECTION, $this->items)) {
            return true;
        }

        $direction = $this->items[ConfigUtil::DIRECTION];

        return
            ConfigUtil::DIRECTION_INPUT_ONLY === $direction
            || ConfigUtil::DIRECTION_BIDIRECTIONAL === $direction;
    }

    /**
     * Indicates whether the response data can contain this field.
     *
     * @return bool
     */
    public function isOutput()
    {
        if (!\array_key_exists(ConfigUtil::DIRECTION, $this->items)) {
            return true;
        }

        $direction = $this->items[ConfigUtil::DIRECTION];

        return
            ConfigUtil::DIRECTION_OUTPUT_ONLY === $direction
            || ConfigUtil::DIRECTION_BIDIRECTIONAL === $direction;
    }

    /**
     * Indicates whether the path of the field value exists.
     *
     * @return bool
     */
    public function hasPropertyPath()
    {
        return $this->has(ConfigUtil::PROPERTY_PATH);
    }

    /**
     * Gets the path of the field value.
     *
     * @param string|null $defaultValue
     *
     * @return string|null
     */
    public function getPropertyPath($defaultValue = null)
    {
        if (empty($this->items[ConfigUtil::PROPERTY_PATH])) {
            return $defaultValue;
        }

        return $this->items[ConfigUtil::PROPERTY_PATH];
    }

    /**
     * Sets the path of the field value.
     *
     * @param string|null $propertyPath
     */
    public function setPropertyPath($propertyPath = null)
    {
        if ($propertyPath) {
            $this->items[ConfigUtil::PROPERTY_PATH] = $propertyPath;
        } else {
            unset($this->items[ConfigUtil::PROPERTY_PATH]);
        }
    }

    /**
     * Gets the form type.
     *
     * @return string|null
     */
    public function getFormType()
    {
        return $this->get(ConfigUtil::FORM_TYPE);
    }

    /**
     * Sets the form type.
     *
     * @param string|null $formType
     */
    public function setFormType($formType)
    {
        if ($formType) {
            $this->items[ConfigUtil::FORM_TYPE] = $formType;
        } else {
            unset($this->items[ConfigUtil::FORM_TYPE]);
        }
    }

    /**
     * Gets the form options.
     *
     * @return array|null
     */
    public function getFormOptions()
    {
        return $this->get(ConfigUtil::FORM_OPTIONS);
    }

    /**
     * Sets the form options.
     *
     * @param array|null $formOptions
     */
    public function setFormOptions($formOptions)
    {
        if ($formOptions) {
            $this->items[ConfigUtil::FORM_OPTIONS] = $formOptions;
        } else {
            unset($this->items[ConfigUtil::FORM_OPTIONS]);
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
        $formOptions = $this->getFormOptions();
        $formOptions[$name] = $value;
        $this->setFormOptions($formOptions);
    }

    /**
     * Gets existing validation constraints from the form options.
     *
     * @return array|null [Constraint object or [constraint name or class => constraint options, ...], ...]
     */
    public function getFormConstraints()
    {
        return FormConstraintUtil::getFormConstraints($this->getFormOptions());
    }

    /**
     * Adds a validation constraint to the form options.
     */
    public function addFormConstraint(Constraint $constraint)
    {
        $this->setFormOptions(FormConstraintUtil::addFormConstraint($this->getFormOptions(), $constraint));
    }

    /**
     * Removes a validation constraint from the form options by its class.
     *
     * @param string $constraintClass
     */
    public function removeFormConstraint($constraintClass)
    {
        $this->setFormOptions(FormConstraintUtil::removeFormConstraint($this->getFormOptions(), $constraintClass));
    }

    /**
     * Indicates whether a post processor is set.
     */
    public function hasPostProcessor(): bool
    {
        return $this->has(ConfigUtil::POST_PROCESSOR);
    }

    /**
     * Gets the type of a post processor.
     */
    public function getPostProcessor(): ?string
    {
        return $this->get(ConfigUtil::POST_PROCESSOR);
    }

    /**
     * Sets the type of a post processor.
     */
    public function setPostProcessor(?string $type): void
    {
        $this->items[ConfigUtil::POST_PROCESSOR] = $type ?: null;
    }

    /**
     * Removes a post processor.
     */
    public function removePostProcessor(): void
    {
        unset($this->items[ConfigUtil::POST_PROCESSOR]);
    }

    /**
     * Gets the options for a post processor.
     */
    public function getPostProcessorOptions(): ?array
    {
        return $this->get(ConfigUtil::POST_PROCESSOR_OPTIONS);
    }

    /**
     * Sets the options for a post processor.
     */
    public function setPostProcessorOptions(?array $options): void
    {
        if ($options) {
            $this->items[ConfigUtil::POST_PROCESSOR_OPTIONS] = $options;
        } else {
            unset($this->items[ConfigUtil::POST_PROCESSOR_OPTIONS]);
        }
    }
}
