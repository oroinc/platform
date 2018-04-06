<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AttributeConfigExtension extends AbstractTypeExtension
{
    use AttributeConfigExtensionApplicableTrait;

    /** @var ConfigProvider */
    protected $attributeConfigProvider;

    /** @var SerializedFieldProvider */
    protected $serializedFieldProvider;

    /** @var AttributeTypeRegistry */
    protected $attributeTypeRegistry;

    /**
     * @param ConfigProvider $attributeConfigProvider
     * @param SerializedFieldProvider $serializedFieldProvider
     * @param AttributeTypeRegistry $attributeTypeRegistry
     */
    public function __construct(
        ConfigProvider $attributeConfigProvider,
        SerializedFieldProvider $serializedFieldProvider,
        AttributeTypeRegistry $attributeTypeRegistry
    ) {
        $this->attributeConfigProvider = $attributeConfigProvider;
        $this->serializedFieldProvider = $serializedFieldProvider;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $configModel = $options['config_model'];
        if ($configModel instanceof FieldConfigModel) {
            if ($this->isApplicable($configModel)) {
                $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
                $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);

                $this->ensureAttributeFields($builder, $configModel);
            } else {
                // Remove fields from 'attribute' scope for regular FieldConfigModel
                $builder->remove('attribute');
            }
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param FieldConfigModel $configModel
     */
    protected function ensureAttributeFields(FormBuilderInterface $builder, FieldConfigModel $configModel)
    {
        if (!$builder->has('attribute')) {
            return;
        }

        $attribute = $builder->get('attribute');

        $attributeType = $this->attributeTypeRegistry->getAttributeType($configModel);
        if (!$attributeType) {
            $attribute->remove('searchable');
            $attribute->remove('filterable');
            $attribute->remove('filter_by');
            $attribute->remove('sortable');

            return;
        }

        if (!$attributeType->isSearchable($configModel)) {
            $attribute->remove('searchable');
        }

        if (!$attributeType->isFilterable($configModel)) {
            $attribute->remove('filterable');
            $attribute->remove('filter_by');
        }

        if (!$attributeType->isSortable($configModel)) {
            $attribute->remove('sortable');
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        $event->getForm()->remove('is_serialized');
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        if ($event->getForm()->isValid()) {
            $configModel = $event->getForm()->getConfig()->getOption('config_model');
            $data = $event->getData();
            $data['extend']['is_serialized'] = $this->serializedFieldProvider->isSerializedByData($configModel, $data);

            $event->setData($data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ConfigType::class;
    }
}
