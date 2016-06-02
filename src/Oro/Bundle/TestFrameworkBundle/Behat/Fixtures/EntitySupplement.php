<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Behat\Behat\Tester\Exception\PendingException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Faker\Factory;
use Faker\ORM\Doctrine\ColumnTypeGuesser;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class EntitySupplement
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ReferenceRepository
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
     * EntitySupplement constructor.
     * @param Registry $registry
     * @param ReferenceRepository $referenceRepository
     */
    public function __construct(Registry $registry, ReferenceRepository $referenceRepository)
    {
        $this->registry = $registry;
        $this->referenceRepository = $referenceRepository;
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->faker = Factory::create();
        $this->columnTypeGuesser = new ColumnTypeGuesser($this->faker);
    }

    /**
     * @param object $object Entity object
     */
    public function completeRequired($object)
    {
        $className = get_class($object);
        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->registry->getManagerForClass($className)->getClassMetadata($className);

        $this->completeFields($object, $metadata);
        $this->setOwnership($object, $metadata);
    }

    /**
     * @param $object
     * @param ClassMetadataInfo $metadata
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function setOwnership($object, ClassMetadataInfo $metadata)
    {
        if (method_exists($object, 'setOwner') && method_exists($object, 'getOwner')) {
            if (!$object->getOwner() && isset($metadata->associationMappings['owner'])) {
                $ownerMapping = $metadata->getAssociationMapping('owner');

                if ($ownerMapping['targetEntity'] === 'Oro\Bundle\UserBundle\Entity\User') {
                    $object->setOwner($this->referenceRepository->references['admin']);
                } elseif ($ownerMapping['targetEntity'] === 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit') {
                    $object->setOwner($this->referenceRepository->references['unit']);
                }
            }
        }

        if (method_exists($object, 'setOrganization') && method_exists($object, 'getOrganization')) {
            if (!$object->getOrganization()) {
                $object->setOrganization($this->referenceRepository->references['organization']);
            }
        }
    }

    /**
     * @param $object
     * @param ClassMetadataInfo $metadata
     */
    protected function completeFields($object, ClassMetadataInfo $metadata)
    {
        foreach ($metadata->getFieldNames() as $fieldName) {
            if (true === $metadata->isNullable($fieldName)
                || true === $metadata->isIdentifier($fieldName)
                || $metadata->getFieldValue($object, $fieldName)
            ) {
                continue;
            }

            $fakeData = $this->columnTypeGuesser->guessFormat($fieldName, $metadata);
            $this->accessor->setValue($object, $fieldName, $fakeData());
        }
    }
}
