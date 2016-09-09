<?php

namespace Oro\Bundle\OrganizationBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;

class OrganizationFormExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;
    
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ServiceLink
     */
    protected $securityFacadeLink;

    /**
     * @var ServiceLink
     */
    protected $metadataProviderLink;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param ManagerRegistry $registry
     * @param ServiceLink $securityFacadeLink
     * @param ServiceLink $metadataProviderLink
     */
    public function __construct(
        ManagerRegistry $registry,
        ServiceLink $securityFacadeLink,
        ServiceLink $metadataProviderLink
    ) {
        $this->registry = $registry;
        $this->securityFacadeLink = $securityFacadeLink;
        $this->metadataProviderLink = $metadataProviderLink;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // listener must be executed before validation
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 128);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $data = $event->getForm()->getData();

        if (is_array($data) || $data instanceof \Traversable) {
            foreach ($data as $value) {
                if (is_object($value)) {
                    $this->updateOrganization($value);
                }
            }
        } elseif (is_object($data)) {
            $this->updateOrganization($data);
        }
    }

    /**
     * @param object $entity
     */
    protected function updateOrganization($entity)
    {
        /** @var OwnershipMetadataProvider $metadataProvider */
        $metadataProvider = $this->metadataProviderLink->getService();

        $organizationField = $metadataProvider->getMetadata(ClassUtils::getClass($entity))->getGlobalOwnerFieldName();
        if (!$organizationField) {
            return;
        }

        $organization = $this->getPropertyAccessor()->getValue($entity, $organizationField);
        if ($organization) {
            return;
        }

        /** @var SecurityFacade $securityFacade */
        $securityFacade = $this->securityFacadeLink->getService();
        $organization = $securityFacade->getOrganization();
        if (!$organization) {
            return;
        }

        $this->getPropertyAccessor()->setValue($entity, $organizationField, $organization);
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
