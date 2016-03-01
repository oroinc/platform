<?php

namespace Oro\Bundle\OrganizationBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class OwnerFormSubscriber implements EventSubscriberInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $fieldName;

    /** @var string */
    protected $fieldLabel;

    /** @var boolean */
    protected $isAssignGranted;

    /** @var object|null*/
    protected $defaultOwner;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $fieldName
     * @param string $fieldLabel
     * @param bool $isAssignGranted this parameter is transmitted as link because value can be changed in form class
     * @param null $defaultOwner
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        $fieldName,
        $fieldLabel,
        &$isAssignGranted,
        $defaultOwner = null
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->fieldName = $fieldName;
        $this->fieldLabel = $fieldLabel;
        $this->isAssignGranted = &$isAssignGranted;
        $this->defaultOwner = $defaultOwner;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::POST_SET_DATA => 'postSetData'
        );
    }

    /**
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->getParent() || !$form->has($this->fieldName)) {
            return;
        }

        $isEntityExists = false;
        $entity = $event->getData();
        if ($entity) {
            if (!is_object($entity)) {
                return;
            }

            $entityClass = ClassUtils::getClass($entity);
            if (!$this->doctrineHelper->isManageableEntity($entityClass)) {
                return;
            }

            $entityIdentifier = $this->doctrineHelper
                ->getEntityManager($entityClass)
                ->getClassMetadata($entityClass)
                ->getIdentifierValues($entity);

            $isEntityExists = !empty($entityIdentifier);
        }

        // if entity exists and assign is not granted - replace field with disabled text field,
        // otherwise - set default owner value
        if ($isEntityExists) {
            $this->replaceOwnerField($form);
        } else {
            $this->setPredefinedOwner($form);
        }
    }

    /**
     * @param FormInterface $form
     */
    protected function replaceOwnerField(FormInterface $form)
    {
        if ($this->isAssignGranted) {
            return;
        }

        $owner = $form->get($this->fieldName)->getData();
        $ownerData = method_exists($owner, 'getName') ? $owner->getName() : (string)$owner;

        $form->remove($this->fieldName);
        $form->add(
            $this->fieldName,
            'text',
            array(
                'disabled' => true,
                'data' => $ownerData ?: '',
                'mapped' => false,
                'required' => false,
                'label' => $this->fieldLabel
            )
        );
    }

    /**
     * @param FormInterface $form
     */
    protected function setPredefinedOwner(FormInterface $form)
    {
        $ownerForm = $form->get($this->fieldName);
        if ($ownerForm->getData()) {
            return;
        }

        if ($this->defaultOwner) {
            $ownerForm->setData($this->defaultOwner);
        }
    }
}
