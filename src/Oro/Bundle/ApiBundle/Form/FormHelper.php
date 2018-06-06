<?php

namespace Oro\Bundle\ApiBundle\Form;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Provides a set of reusable utility methods to simplify
 * creation and configuration of FormBuilder for forms used in Data API actions,
 * such as "create", "update",
 * "update_subresource", "add_subresource" and "delete_subresource",
 * "update_relationship", "add_relationship" and "delete_relationship".
 */
class FormHelper
{
    public const EXTRA_FIELDS_MESSAGE = 'oro.api.form.extra_fields';

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param FormFactoryInterface      $formFactory
     * @param PropertyAccessorInterface $propertyAccessor
     * @param ContainerInterface        $container
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        PropertyAccessorInterface $propertyAccessor,
        ContainerInterface $container
    ) {
        $this->formFactory = $formFactory;
        $this->propertyAccessor = $propertyAccessor;
        $this->container = $container;
    }

    /**
     * Creates a form builder.
     * Please note that the form validation is disabled by default,
     * to enable it use "enable_validation" option.
     * @see getFormDefaultOptions to find all default options
     *
     * @param string     $formType
     * @param mixed      $data
     * @param array      $options
     * @param array|null $eventSubscribers
     *
     * @return FormBuilderInterface
     */
    public function createFormBuilder($formType, $data, array $options, array $eventSubscribers = null)
    {
        $formBuilder = $this->formFactory->createNamedBuilder(
            null,
            $formType,
            $data,
            \array_merge($this->getFormDefaultOptions(), $options)
        );
        $formBuilder->setDataMapper(new PropertyPathMapper($this->propertyAccessor));
        if (!empty($eventSubscribers)) {
            $this->addFormEventSubscribers($formBuilder, $eventSubscribers);
        }

        return $formBuilder;
    }

    /**
     * Adds all entity fields to the given form.
     *
     * @param FormBuilderInterface   $formBuilder
     * @param EntityMetadata         $entityMetadata
     * @param EntityDefinitionConfig $entityConfig
     */
    public function addFormFields(
        FormBuilderInterface $formBuilder,
        EntityMetadata $entityMetadata,
        EntityDefinitionConfig $entityConfig
    ) {
        $fields = $entityMetadata->getFields();
        foreach ($fields as $name => $field) {
            if (!$field->isInput()) {
                continue;
            }
            $this->addFormField($formBuilder, $name, $entityConfig->getField($name), $field);
        }
        $associations = $entityMetadata->getAssociations();
        foreach ($associations as $name => $association) {
            if (!$association->isInput()) {
                continue;
            }
            $this->addFormField($formBuilder, $name, $entityConfig->getField($name), $association);
        }
    }

    /**
     * Adds a field to the given form.
     *
     * @param FormBuilderInterface        $formBuilder
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $fieldConfig
     * @param PropertyMetadata            $fieldMetadata
     * @param array                       $options
     *
     * @return FormBuilderInterface
     */
    public function addFormField(
        FormBuilderInterface $formBuilder,
        $fieldName,
        EntityDefinitionFieldConfig $fieldConfig,
        PropertyMetadata $fieldMetadata,
        array $options = []
    ) {
        $fieldFormBuilder = $formBuilder->add(
            $fieldName,
            $fieldConfig->getFormType(),
            \array_replace($options, $this->getFormFieldOptions($fieldMetadata, $fieldConfig))
        );

        $targetConfig = $fieldConfig->getTargetEntity();
        if (null !== $targetConfig) {
            $eventSubscribers = $targetConfig->getFormEventSubscribers();
            if (!empty($eventSubscribers)) {
                $this->addFormEventSubscribers($fieldFormBuilder, $eventSubscribers);
            }
        }

        return $fieldFormBuilder;
    }

    /**
     * Returns default options of a form.
     *
     * @return array
     */
    private function getFormDefaultOptions()
    {
        return [
            'validation_groups'    => ['Default', 'api'],
            'extra_fields_message' => self::EXTRA_FIELDS_MESSAGE,
            'enable_validation'    => false
        ];
    }

    /**
     * Gets options of a form field.
     *
     * @param PropertyMetadata            $property
     * @param EntityDefinitionFieldConfig $config
     *
     * @return array
     */
    private function getFormFieldOptions(PropertyMetadata $property, EntityDefinitionFieldConfig $config)
    {
        $options = $config->getFormOptions();
        if (null === $options) {
            $options = [];
        }
        if (!\array_key_exists('property_path', $options)) {
            $propertyPath = $property->getPropertyPath();
            if (!$propertyPath) {
                $options['mapped'] = false;
            } elseif ($propertyPath !== $property->getName()) {
                $options['property_path'] = $propertyPath;
            }
        }

        return $options;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @param array                $eventSubscribers
     */
    private function addFormEventSubscribers(FormBuilderInterface $formBuilder, array $eventSubscribers)
    {
        foreach ($eventSubscribers as $eventSubscriber) {
            if (\is_string($eventSubscriber)) {
                $eventSubscriber = $this->container->get($eventSubscriber);
            }
            $formBuilder->addEventSubscriber($eventSubscriber);
        }
    }
}
