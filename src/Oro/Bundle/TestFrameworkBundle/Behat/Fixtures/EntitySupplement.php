<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Faker\Factory;
use Faker\ORM\Doctrine\ColumnTypeGuesser;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Util\ClassUtils;

class EntitySupplement
{
    /**
     * @var Registry
     */
    protected $registry;

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
     * @var MetadataProviderInterface
     */
    protected $metadataProvider;

    /**
     * EntitySupplement constructor.
     * @param Registry $registry
     * @param AliceCollection $referenceRepository
     * @param MetadataProviderInterface $metadataProvider
     */
    public function __construct(
        Registry $registry,
        AliceCollection $referenceRepository,
        MetadataProviderInterface $metadataProvider
    ) {
        $this->registry = $registry;
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
        $metadata = $this->registry->getManagerForClass($className)->getClassMetadata($className);

        $this->setValues($entity, $values);
        $this->completeFields($entity, $metadata);
        $this->setOwnership($entity);
    }

    /**
     * @param object $entity
     */
    protected function setOwnership($entity)
    {
        /** @var OwnershipMetadata $ownershipMetadata */
        $ownershipMetadata = $this->metadataProvider->getMetadata(ClassUtils::getRealClass($entity));
        $ownerField = $ownershipMetadata->getOwnerFieldName();
        $organizationField = $ownershipMetadata->getGlobalOwnerFieldName();

        if ($ownerField && !$this->accessor->getValue($entity, $ownerField)) {
            if ($ownershipMetadata->isBasicLevelOwned()) {
                $this->accessor->setValue($entity, $ownerField, $this->referenceRepository->get('admin'));
            } elseif ($ownershipMetadata->isLocalLevelOwned()) {
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
