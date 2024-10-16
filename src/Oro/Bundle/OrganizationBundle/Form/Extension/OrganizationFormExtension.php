<?php

namespace Oro\Bundle\OrganizationBundle\Form\Extension;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Sets organization for entity if such field exists
 */
class OrganizationFormExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    protected PropertyAccessorInterface $propertyAccessor;
    protected TokenAccessorInterface $tokenAccessor;
    protected OwnershipMetadataProviderInterface $ownershipMetadataProvider;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        TokenAccessorInterface $tokenAccessor,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->tokenAccessor = $tokenAccessor;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //PRE_SUBMIT needed to set correct organization before other form extensions executes their logic
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPostSubmit'], 128);
        // listener must be executed before validation
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 128);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $data = $event->getForm()->getData();
        if (is_iterable($data)) {
            foreach ($data as $value) {
                if (\is_object($value)) {
                    $this->updateOrganization($value);
                }
            }
        } elseif (\is_object($data)) {
            $this->updateOrganization($data);
        }
    }

    protected function updateOrganization(object $entity): void
    {
        $organizationField = $this->ownershipMetadataProvider
            ->getMetadata(ClassUtils::getClass($entity))
            ->getOrganizationFieldName();
        if (!$organizationField) {
            return;
        }

        $organization = $this->propertyAccessor->getValue($entity, $organizationField);
        if ($organization) {
            return;
        }

        $organization = $this->tokenAccessor->getOrganization();
        if (null === $organization) {
            return;
        }

        $this->propertyAccessor->setValue($entity, $organizationField, $organization);
    }
}
