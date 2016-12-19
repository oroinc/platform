<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AttributeConfigExtension extends AbstractTypeExtension
{
    /**
     * @var ConfigProvider
     */
    protected $attributeConfigProvider;

    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @var SerializedFieldProvider
     */
    protected $serializedFieldProvider;

    /**
     * @param ConfigProvider $attributeConfigProvider
     * @param ConfigProvider $extendConfigProvider
     * @param SerializedFieldProvider $serializedFieldProvider
     */
    public function __construct(
        ConfigProvider $attributeConfigProvider,
        ConfigProvider $extendConfigProvider,
        SerializedFieldProvider $serializedFieldProvider
    ) {
        $this->attributeConfigProvider = $attributeConfigProvider;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->serializedFieldProvider = $serializedFieldProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $configModel = $options['config_model'];
        if ($configModel instanceof FieldConfigModel) {
            $className = $configModel->getEntity()->getClassName();
            $fieldName = $configModel->getFieldName();

            $hasAttributes = $this->attributeConfigProvider->getConfig($className)->is('has_attributes');
            $isAttribute = $this->attributeConfigProvider->getConfig($className, $fieldName)->is('is_attribute');
            if ($hasAttributes && $isAttribute) {
                $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
                $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
            } else {
                // Remove fields from 'attribute' scope for regular FieldConfigModel
                $builder->remove('attribute');
            }
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
            $isSerialized = $this->serializedFieldProvider->isSerializedByData($configModel, $event->getData());
            $extendConfig = $this->extendConfigProvider->getConfig(
                $configModel->getEntity()->getClassName(),
                $configModel->getFieldName()
            );
            $extendConfig->set('is_serialized', $isSerialized);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_entity_config_type';
    }
}
