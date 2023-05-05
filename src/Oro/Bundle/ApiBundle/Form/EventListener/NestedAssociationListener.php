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

/**
 * Sets "__class__" and "id" configuration properties for a nested association.
 */
class NestedAssociationListener implements EventSubscriberInterface
{
    private PropertyAccessorInterface $propertyAccessor;
    private EntityDefinitionFieldConfig $config;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        EntityDefinitionFieldConfig $config
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit'
        ];
    }

    public function postSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $form->getData();
        $entity = $form->getParent()->getData();
        $entityConfig = $this->config->getTargetEntity();

        $className = null;
        $id = null;
        if (null !== $data) {
            if (!$data instanceof EntityIdentifier) {
                throw new \UnexpectedValueException(sprintf(
                    'Expected argument of type "%s", "%s" given.',
                    EntityIdentifier::class,
                    get_debug_type($data)
                ));
            }
            $className = $data->getClass();
            $id = $data->getId();
        }
        $this->setPropertyValue($entityConfig, $entity, ConfigUtil::CLASS_NAME, $className);
        $this->setPropertyValue($entityConfig, $entity, 'id', $id);
    }

    private function setPropertyValue(
        EntityDefinitionConfig $entityConfig,
        object $entity,
        string $fieldName,
        mixed $value
    ): void {
        $this->propertyAccessor->setValue(
            $entity,
            $entityConfig->getField($fieldName)->getPropertyPath($fieldName),
            $value
        );
    }
}
