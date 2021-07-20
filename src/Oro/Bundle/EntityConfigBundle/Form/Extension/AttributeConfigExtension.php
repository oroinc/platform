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

/**
 * Reorganizes form fields on the attribute configuration page
 */
class AttributeConfigExtension extends AbstractTypeExtension
{
    use AttributeConfigExtensionApplicableTrait;

    /** @var ConfigProvider */
    protected $attributeConfigProvider;

    /** @var SerializedFieldProvider */
    protected $serializedFieldProvider;

    /** @var AttributeTypeRegistry */
    protected $attributeTypeRegistry;

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

    protected function ensureAttributeFields(FormBuilderInterface $builder, FieldConfigModel $configModel)
    {
        if (!$builder->has('attribute')) {
            return;
        }

        $attributeType = $this->attributeTypeRegistry->getAttributeType($configModel);
        if (!$attributeType) {
            $builder->remove('attribute');

            return;
        }

        $attribute = $builder->get('attribute');

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

    public function onPostSetData(FormEvent $event)
    {
        $event->getForm()->remove('is_serialized');
    }

    public function onPostSubmit(FormEvent $event)
    {
        if ($event->getForm()->isValid()) {
            $configModel = $event->getForm()->getConfig()->getOption('config_model');
            if (!$configModel->getId()) {
                $data = $event->getData();
                $isSerialized = $this->serializedFieldProvider->isSerializedByData($configModel, $data);
                $data['extend']['is_serialized'] = $isSerialized;

                $event->setData($data);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ConfigType::class];
    }
}
