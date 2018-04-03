<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AttributeFieldConfigExtension extends AbstractTypeExtension
{
    /**
     * @var ConfigProvider
     */
    protected $attributeConfigProvider;

    /**
     * @param ConfigProvider $attributeConfigProvider
     */
    public function __construct(ConfigProvider $attributeConfigProvider)
    {
        $this->attributeConfigProvider = $attributeConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var FieldConfigModel $entity */
        $entity = $event->getData();
        if (!$entity) {
            return;
        }

        $className = $entity->getEntity()->getClassName();
        if (!$this->attributeConfigProvider->getConfig($className)->is('has_attributes')) {
            return;
        }

        if (!empty($entity->toArray('attribute')['is_attribute'])) {
            $event->getForm()->remove('is_serialized');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FieldType::class;
    }
}
