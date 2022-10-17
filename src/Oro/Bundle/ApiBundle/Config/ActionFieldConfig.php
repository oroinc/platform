<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\EntitySerializer\FieldConfigInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Represents a field configuration inside "actions" section.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ActionFieldConfig implements FieldConfigInterface
{
    private ?bool $exclude = null;
    private array $items = [];

    /**
     * Gets a native PHP array representation of the configuration.
     */
    public function toArray(): array
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
     * Indicates whether the configuration attribute exists.
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * Gets the configuration value.
     */
    public function get(string $key, mixed $defaultValue = null): mixed
    {
        if (!\array_key_exists($key, $this->items)) {
            return $defaultValue;
        }

        return $this->items[$key];
    }

    /**
     * Sets the configuration value.
     */
    public function set(string $key, mixed $value): void
    {
        if (null !== $value) {
            $this->items[$key] = $value;
        } else {
            unset($this->items[$key]);
        }
    }

    /**
     * Removes the configuration value.
     */
    public function remove(string $key): void
    {
        unset($this->items[$key]);
    }

    /**
     * Gets names of all configuration attributes.
     *
     * @return string[]
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    /**
     * Indicates whether the exclusion flag is set explicitly.
     */
    public function hasExcluded(): bool
    {
        return null !== $this->exclude;
    }

    /**
     * Indicates whether the exclusion flag.
     */
    public function isExcluded(): bool
    {
        return $this->exclude ?? false;
    }

    /**
     * Sets the exclusion flag.
     *
     * @param bool|null $exclude The exclude flag or NULL to remove this option
     */
    public function setExcluded(?bool $exclude = true): void
    {
        $this->exclude = $exclude;
    }

    /**
     * Indicates whether the direction option is set explicitly.
     * If this option is not set, both the request and the response can contain this field.
     */
    public function hasDirection(): bool
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
    public function setDirection(?string $direction): void
    {
        if ($direction) {
            if (ConfigUtil::DIRECTION_INPUT_ONLY !== $direction
                && ConfigUtil::DIRECTION_OUTPUT_ONLY !== $direction
                && ConfigUtil::DIRECTION_BIDIRECTIONAL !== $direction
            ) {
                throw new \InvalidArgumentException(sprintf(
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
     */
    public function isInput(): bool
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
     */
    public function isOutput(): bool
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
     */
    public function hasPropertyPath(): bool
    {
        return $this->has(ConfigUtil::PROPERTY_PATH);
    }

    /**
     * Gets the path of the field value.
     */
    public function getPropertyPath(string $defaultValue = null): ?string
    {
        if (empty($this->items[ConfigUtil::PROPERTY_PATH])) {
            return $defaultValue;
        }

        return $this->items[ConfigUtil::PROPERTY_PATH];
    }

    /**
     * Sets the path of the field value.
     */
    public function setPropertyPath(string $propertyPath = null): void
    {
        if ($propertyPath) {
            $this->items[ConfigUtil::PROPERTY_PATH] = $propertyPath;
        } else {
            unset($this->items[ConfigUtil::PROPERTY_PATH]);
        }
    }

    /**
     * Gets the form type.
     */
    public function getFormType(): ?string
    {
        return $this->get(ConfigUtil::FORM_TYPE);
    }

    /**
     * Sets the form type.
     */
    public function setFormType(?string $formType): void
    {
        if ($formType) {
            $this->items[ConfigUtil::FORM_TYPE] = $formType;
        } else {
            unset($this->items[ConfigUtil::FORM_TYPE]);
        }
    }

    /**
     * Gets the form options.
     */
    public function getFormOptions(): ?array
    {
        return $this->get(ConfigUtil::FORM_OPTIONS);
    }

    /**
     * Sets the form options.
     */
    public function setFormOptions(?array $formOptions): void
    {
        if ($formOptions) {
            $this->items[ConfigUtil::FORM_OPTIONS] = $formOptions;
        } else {
            unset($this->items[ConfigUtil::FORM_OPTIONS]);
        }
    }

    /**
     * Sets a form option. If an option is already exist its value will be replaced with new value.
     */
    public function setFormOption(string $name, mixed $value): void
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
    public function getFormConstraints(): ?array
    {
        return FormConstraintUtil::getFormConstraints($this->getFormOptions());
    }

    /**
     * Adds a validation constraint to the form options.
     */
    public function addFormConstraint(Constraint $constraint): void
    {
        $this->setFormOptions(FormConstraintUtil::addFormConstraint($this->getFormOptions(), $constraint));
    }

    /**
     * Removes a validation constraint from the form options by its class.
     */
    public function removeFormConstraint(string $constraintClass): void
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
