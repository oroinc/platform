<?php

namespace Oro\Bundle\OrganizationBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class OrganizationFormExtension extends AbstractTypeExtension
{
    const RELATION_NAME = 'organization';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param ManagerRegistry $registry
     * @param SecurityFacade $securityFacade
     */
    public function __construct(ManagerRegistry $registry, SecurityFacade $securityFacade)
    {
        $this->registry = $registry;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['data_class']) && $this->isClassSupported($options['data_class'])) {
            // listener must be executed before validation
            $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 128);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $entity = $event->getData();
        $organization = $this->securityFacade->getOrganization();
        if ($entity && $organization) {
            $propertyAccessor = $this->getPropertyAccessor();
            if (!$propertyAccessor->getValue($entity, self::RELATION_NAME)) {
                $propertyAccessor->setValue($entity, self::RELATION_NAME, $organization);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * @param string $class
     * @return bool
     */
    protected function isClassSupported($class)
    {
        $entityManager = $this->registry->getManagerForClass($class);
        if ($entityManager) {
            return $entityManager->getClassMetadata($class)->hasAssociation(self::RELATION_NAME);
        }

        return false;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
