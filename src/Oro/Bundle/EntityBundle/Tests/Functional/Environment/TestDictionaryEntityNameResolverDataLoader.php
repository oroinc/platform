<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class TestDictionaryEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;
    private ConfigManager $configManager;
    private ManagerRegistry $doctrine;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        TestEntityNameResolverDataLoaderInterface $innerDataLoader,
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->innerDataLoader = $innerDataLoader;
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
        $this->propertyAccessor = $propertyAccessor;
    }

    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if ($this->isDictionary($entityClass)) {
            $entity = $this->createEntity($entityClass);
            if (null !== $entity) {
                $entityReference = 'dictionary_' . substr($entityClass, strrpos($entityClass, '\\') + 1);
                $repository->setReference($entityReference, $entity);
                $em->persist($entity);
                $em->flush();

                return [$entityReference];
            }
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    #[\Override]
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if ($this->isDictionary($entityClass)) {
            return $this->propertyAccessor->getValue(
                $repository->getReference($entityReference),
                $this->getDictionaryLabelFieldName($entityClass)
            );
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }

    private function isDictionary(string $entityClass): bool
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return false;
        }

        $groups = $this->configManager->getEntityConfig('grouping', $entityClass)->get('groups');

        return
            $groups
            && \in_array('dictionary', $groups, true)
            && $this->getDictionaryIdFieldName($entityClass)
            && $this->getDictionaryLabelFieldName($entityClass);
    }

    private function getDictionaryIdFieldName(string $entityClass): ?string
    {
        $metadata = $this->getEntityMetadata($entityClass);
        if (null === $metadata || $metadata->usesIdGenerator()) {
            return null;
        }

        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (\count($idFieldNames) !== 1) {
            return null;
        }

        $idFieldName = reset($idFieldNames);
        if ($metadata->getTypeOfField($idFieldName) !== Types::STRING) {
            return null;
        }

        return $idFieldName;
    }

    private function getDictionaryLabelFieldName(string $entityClass): ?string
    {
        $metadata = $this->getEntityMetadata($entityClass);
        if (null === $metadata) {
            return null;
        }

        $fieldName = $this->configManager->getEntityConfig('dictionary', $entityClass)
            ->get('representation_field', false, 'label');
        if (!$metadata->hasField($fieldName)) {
            return null;
        }

        if ($metadata->getTypeOfField($fieldName) !== Types::STRING) {
            return null;
        }

        return $fieldName;
    }

    private function getDictionaryOrderFieldName(string $entityClass): ?string
    {
        $metadata = $this->getEntityMetadata($entityClass);
        if (null === $metadata) {
            return null;
        }

        $fieldName = 'order';
        if (!$metadata->hasField($fieldName)) {
            return null;
        }

        if ($metadata->getTypeOfField($fieldName) !== Types::INTEGER) {
            return null;
        }

        return $fieldName;
    }

    private function createEntity(string $entityClass): ?object
    {
        $entity = null;
        try {
            $entity = new $entityClass();
            $this->propertyAccessor->setValue($entity, $this->getDictionaryIdFieldName($entityClass), 'test_item');
        } catch (\ArgumentCountError) {
            try {
                $entity = new $entityClass('test_item');
            } catch (\ArgumentCountError) {
                // ignore construct exception
            }
        }
        if (null !== $entity) {
            $this->propertyAccessor->setValue($entity, $this->getDictionaryLabelFieldName($entityClass), 'Test Item');
            $orderFieldName = $this->getDictionaryOrderFieldName($entityClass);
            if ($orderFieldName) {
                $this->propertyAccessor->setValue($entity, $orderFieldName, 100);
            }
        }

        return $entity;
    }

    private function getEntityMetadata(string $entityClass): ?ClassMetadata
    {
        $metadata = $this->doctrine->getManagerForClass($entityClass)->getClassMetadata($entityClass);

        return $metadata instanceof ClassMetadata ? $metadata : null;
    }
}
