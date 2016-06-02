<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Behat\Behat\Tester\Exception\PendingException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Faker\Factory;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class EntitySupplement
{
    /**
     * @var Factory
     */
    protected $faker;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ReferenceRepository
     */
    protected $referenceRepository;

    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    /**
     * EntitySupplement constructor.
     * @param Registry $registry
     * @param ReferenceRepository $referenceRepository
     */
    public function __construct(Registry $registry, ReferenceRepository $referenceRepository)
    {
        $this->faker = Factory::create();
        $this->registry = $registry;
        $this->referenceRepository = $referenceRepository;
        $this->accessor = PropertyAccess::createPropertyAccessor();
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
     * @param ClassMetadataInfo $metadata
     * @param $fieldName
     * @return string
     */
    protected function getType(ClassMetadataInfo $metadata, $fieldName)
    {
        $type = $metadata->getTypeOfField($fieldName);

        if (is_object($type) && is_subclass_of($type, 'Doctrine\DBAL\Types\Type')) {
            return $type->getName();
        } elseif (is_string($type)) {
            return $type;
        }

        throw new \InvalidArgumentException();
    }

    /**
     * @param $type
     * @return float|int|string
     */
    protected function getFakeByType($type)
    {
        switch ($type) {
            case Type::TARRAY:
                return $this->faker->words(3, false);
            case Type::SIMPLE_ARRAY:
                return $this->faker->words(3, false);
            case Type::BIGINT:
                return $this->faker->randomNumber();
            case Type::BOOLEAN:
                return $this->faker->boolean(50);
            case Type::DATETIME:
                return $this->faker->dateTime;
            case Type::DATETIMETZ:
                return $this->faker->dateTime;
            case Type::DATE:
                return $this->faker->date();
            case Type::TIME:
                return $this->faker->time();
            case Type::DECIMAL:
                return $this->faker->randomFloat(2);
            case Type::INTEGER:
                return $this->faker->randomNumber();
            case Type::OBJECT:
                return new \stdClass();
            case Type::SMALLINT:
                return $this->faker->randomNumber(5);
            case Type::STRING:
                return $this->faker->sentence(3);
            case Type::TEXT:
                return $this->faker->paragraph(3);
            case Type::FLOAT:
                return $this->faker->randomFloat(2);
            default:
                throw new PendingException();
        }
    }

    /**
     * @param $object
     * @param $metadata
     */
    protected function completeFields($object, $metadata)
    {
        foreach ($metadata->getFieldNames() as $fieldName) {
            if (true === $metadata->isNullable($fieldName)
                || true === $metadata->isIdentifier($fieldName)
                || $metadata->getFieldValue($object, $fieldName)
            ) {
                continue;
            }

            $type = $this->getType($metadata, $fieldName);
            $fakeData = $this->getFakeByType($type);
            $this->accessor->setValue($object, $fieldName, $fakeData);
        }
    }
}
