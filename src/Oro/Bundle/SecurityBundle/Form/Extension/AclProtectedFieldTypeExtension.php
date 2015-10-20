<?php

namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Acl\Voter\FieldVote;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class AclProtectedFieldTypeExtension extends AbstractTypeExtension
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (empty($options['data_class'])) {
            // apply extension only to forms that bound to some class
            return;
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if (empty($data)) {
            return;
        }

        $className = $form->getConfig()->getDataClass();
        $securityFacade = $this->securityFacade;

        $isGranted = function ($fieldName) use ($form, $className, $securityFacade) {
//            $propertyPath = $childConfig->getPropertyPath();
//            $isRequired = $childConfig->getRequired();
            $childName = $fieldName; // TODO: guess field name from property path

            $childConfig = $form->get($childName)->getConfig();
            $isMapped = $childConfig->getMapped();

            $entity = $form->getData();
            if (false === $isMapped || !$entity instanceof $className) {
                $isGranted = true;
            } else {
                $isGranted = $securityFacade->isGranted('EDIT', new FieldVote($form->getData(), $fieldName));
            }

            return $isGranted;
        };

        $allowedKeys = array_filter(
            array_keys($data),
            function ($childName) use ($isGranted) {
                return $isGranted($childName);
            }
        );
        $data = array_intersect_key($data, array_flip($allowedKeys));

        $event->setData($data);
    }
}
