<?php

namespace Oro\Bundle\ApiBundle\Form;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;

class FormHelper
{
    const EXTRA_FIELDS_MESSAGE = 'oro.api.form.extra_fields';

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ContainerInterface   $container
     */
    public function __construct(FormFactoryInterface $formFactory, ContainerInterface $container)
    {
        $this->formFactory = $formFactory;
        $this->container = $container;
    }

    /**
     * Creates a form builder.
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
            array_merge($this->getFormDefaultOptions(), $options)
        );
        if (!empty($eventSubscribers)) {
            foreach ($eventSubscribers as $eventSubscriber) {
                if (is_string($eventSubscriber)) {
                    $eventSubscriber = $this->container->get($eventSubscriber);
                }
                $formBuilder->addEventSubscriber($eventSubscriber);
            }
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
            $this->addFormField($formBuilder, $name, $entityConfig->getField($name), $field);
        }
        $associations = $entityMetadata->getAssociations();
        foreach ($associations as $name => $association) {
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
     *
     * @return FormBuilderInterface
     */
    public function addFormField(
        FormBuilderInterface $formBuilder,
        $fieldName,
        EntityDefinitionFieldConfig $fieldConfig,
        PropertyMetadata $fieldMetadata
    ) {
        return $formBuilder->add(
            $fieldName,
            $fieldConfig->getFormType(),
            $this->getFormFieldOptions($fieldMetadata, $fieldConfig)
        );
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
            'extra_fields_message' => self::EXTRA_FIELDS_MESSAGE
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
        $propertyPath = $property->getPropertyPath();
        if (!$propertyPath) {
            $options['mapped'] = false;
        } elseif ($propertyPath !== $property->getName()) {
            $options['property_path'] = $propertyPath;
        }

        return $options;
    }
}
