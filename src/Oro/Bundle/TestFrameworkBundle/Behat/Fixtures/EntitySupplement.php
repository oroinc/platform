<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Faker\Factory;
use Faker\ORM\Doctrine\ColumnTypeGuesser;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Acl\Util\ClassUtils;

class EntitySupplement
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var AliceCollection
     */
    protected $referenceRepository;

    /**
     * @var Factory
     */
    protected $faker;

    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    /**
     * @var ColumnTypeGuesser
     */
    protected $columnTypeGuesser;

    /**
     * @var OwnershipMetadataProviderInterface
     */
    protected $metadataProvider;

    /**
     * @param KernelInterface $kernel
     * @param AliceCollection $referenceRepository
     * @param OwnershipMetadataProviderInterface $metadataProvider
     */
    public function __construct(
        KernelInterface $kernel,
        AliceCollection $referenceRepository,
        OwnershipMetadataProviderInterface $metadataProvider
    ) {
        $this->kernel = $kernel;
        $this->referenceRepository = $referenceRepository;
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->faker = Factory::create();
        $this->columnTypeGuesser = new ColumnTypeGuesser($this->faker);
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * @param object $entity
     * @param array $values
     */
    public function completeRequired($entity, array $values = [])
    {
        $className = get_class($entity);
        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->getEntityManager()->getClassMetadata($className);

        $this->setValues($entity, $values);
        $this->completeFields($entity, $metadata);
        $this->setOwnership($entity);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param object $entity
     */
    protected function setOwnership($entity)
    {
        /** @var OwnershipMetadata $ownershipMetadata */
        $ownershipMetadata = $this->metadataProvider->getMetadata(ClassUtils::getRealClass($entity));
        $ownerField = $ownershipMetadata->getOwnerFieldName();
        $organizationField = $ownershipMetadata->getOrganizationFieldName();

        if ($ownerField && !$this->accessor->getValue($entity, $ownerField)) {
            if ($ownershipMetadata->isUserOwned()) {
                $this->accessor->setValue($entity, $ownerField, $this->referenceRepository->get('admin'));
            } elseif ($ownershipMetadata->isBusinessUnitOwned()) {
                $entity->setOwner($this->referenceRepository->get('business_unit'));
            }
        }

        if ($organizationField && !$this->accessor->getValue($entity, $organizationField)) {
            $entity->setOrganization($this->referenceRepository->get('organization'));
        }
    }

    /**
     * @param object $entity
     * @param ClassMetadataInfo $metadata
     */
    protected function completeFields($entity, ClassMetadataInfo $metadata)
    {
        foreach ($metadata->getFieldNames() as $fieldName) {
            if ($metadata->isNullable($fieldName)
                || true === $metadata->isIdentifier($fieldName)
                || $metadata->getFieldValue($entity, $fieldName)
            ) {
                continue;
            }

            $fakeData = $this->columnTypeGuesser->guessFormat($fieldName, $metadata);
            $this->accessor->setValue($entity, $fieldName, $fakeData());
        }
    }

    /**
     * @param object $entity
     * @param array $values
     */
    public function setValues($entity, array $values)
    {
        foreach ($values as $property => $value) {
            $this->accessor->setValue($entity, $property, $value);
        }
    }
}
