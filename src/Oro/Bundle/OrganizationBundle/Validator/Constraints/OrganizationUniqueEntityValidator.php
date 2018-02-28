<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Validator\Constraint;

class OrganizationUniqueEntityValidator extends UniqueEntityValidator
{
    /** @var OwnershipMetadataProviderInterface */
    protected $metadataProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ManagerRegistry                    $registry
     * @param OwnershipMetadataProviderInterface $metadataProvider
     * @param DoctrineHelper                     $doctrineHelper
     */
    public function __construct(
        ManagerRegistry $registry,
        OwnershipMetadataProviderInterface $metadataProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->metadataProvider = $metadataProvider;
        $this->doctrineHelper = $doctrineHelper;

        parent::__construct($registry);
    }

    /**
     * {@inheritdoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        $className = $this->doctrineHelper->getEntityClass($entity);
        $organizationField = $this->metadataProvider->getMetadata($className)->getOrganizationFieldName();
        if ($organizationField) {
            $constraint->fields = array_merge(
                (array) $constraint->fields,
                [$organizationField]
            );
        }

        parent::validate($entity, $constraint);
    }
}
