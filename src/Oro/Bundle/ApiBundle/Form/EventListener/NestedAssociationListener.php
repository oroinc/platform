<?php

namespace Oro\Bundle\ApiBundle\Form\EventListener;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class NestedAssociationListener implements EventSubscriberInterface
{
    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var EntityDefinitionFieldConfig */
    protected $config;

    /**
     * @param PropertyAccessorInterface   $propertyAccessor
     * @param EntityDefinitionFieldConfig $config
     */
    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        EntityDefinitionFieldConfig $config
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $form->getData();
        $entity = $form->getParent()->getData();
        $entityConfig = $this->config->getTargetEntity();

        $className = null;
        $id = null;
        if (null !== $data) {
            if (!$data instanceof EntityIdentifier) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'Expected argument of type "%s", "%s" given.',
                        EntityIdentifier::class,
                        is_object($data) ? get_class($data) : gettype($data)
                    )
                );
            }
            $className = $data->getClass();
            $id = $data->getId();
        }
        $this->setPropertyValue($entityConfig, $entity, ConfigUtil::CLASS_NAME, $className);
        $this->setPropertyValue($entityConfig, $entity, 'id', $id);
    }

    /**
     * @param EntityDefinitionConfig $entityConfig
     * @param object                 $entity
     * @param string                 $fieldName
     * @param mixed                  $value
     */
    protected function setPropertyValue(
        EntityDefinitionConfig $entityConfig,
        $entity,
        $fieldName,
        $value
    ) {
        $this->propertyAccessor->setValue(
            $entity,
            $entityConfig->getField($fieldName)->getPropertyPath($fieldName),
            $value
        );
    }
}
