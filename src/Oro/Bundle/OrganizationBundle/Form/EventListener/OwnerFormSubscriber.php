<?php

namespace Oro\Bundle\OrganizationBundle\Form\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Subscriber for post send owner data.
 */
class OwnerFormSubscriber implements EventSubscriberInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var string */
    protected $fieldName;

    /** @var string */
    protected $fieldLabel;

    /** @var boolean */
    protected $isAssignGranted;

    /** @var object|null*/
    protected $defaultOwner;

    /**
     * @param ManagerRegistry $doctrine
     * @param string $fieldName
     * @param string $fieldLabel
     * @param bool $isAssignGranted this parameter is transmitted as link because value can be changed in form class
     * @param null $defaultOwner
     */
    public function __construct(
        ManagerRegistry $doctrine,
        $fieldName,
        $fieldLabel,
        &$isAssignGranted,
        $defaultOwner = null
    ) {
        $this->doctrine = $doctrine;
        $this->fieldName = $fieldName;
        $this->fieldLabel = $fieldLabel;
        $this->isAssignGranted = &$isAssignGranted;
        $this->defaultOwner = $defaultOwner;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData'
        ];
    }

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
            $em = $this->doctrine->getManagerForClass($entityClass);
            if (null === $em) {
                return;
            }

            $entityIdentifier = $em->getClassMetadata($entityClass)->getIdentifierValues($entity);

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

    protected function replaceOwnerField(FormInterface $form)
    {
        if ($this->isAssignGranted) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $owner = $form->get($this->fieldName)->getData();
        $ownerData = $accessor->isReadable($owner, 'name')
            ? $accessor->getValue($owner, 'name')
            : (string)$owner;

        $form->remove($this->fieldName);
        $form->add(
            $this->fieldName,
            TextType::class,
            [
                'disabled' => true,
                'data' => $ownerData ?: '',
                'mapped' => false,
                'required' => false,
                'label' => $this->fieldLabel
            ]
        );
    }

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
