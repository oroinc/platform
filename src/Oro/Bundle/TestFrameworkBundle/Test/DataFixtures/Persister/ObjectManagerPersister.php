<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Persister;

use Doctrine\ORM\Id\AssignedGenerator as ORMAssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadataInfo as ORMClassMetadataInfo;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Fidry\AliceDataFixtures\Persistence\PersisterInterface;
use Nelmio\Alice\IsAServiceTrait;

/**
 * This file is inspired by {@see \Fidry\AliceDataFixtures\Bridge\Doctrine\Persister\ObjectManagerPersister}
 * with the doctrine/persistence 2.0 support
 */
class ObjectManagerPersister implements PersisterInterface
{
    use IsAServiceTrait;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var array|null Values are FQCN of persistable objects
     */
    private $persistableClasses;

    /**
     * @var ClassMetadata[] Entity metadata, FQCN being the key
     */
    private $metadata = [];

    public function __construct(ObjectManager $manager)
    {
        $this->objectManager = $manager;
    }

    /**
     * @inheritdoc
     */
    public function persist($object): void
    {
        if (null === $this->persistableClasses) {
            $this->persistableClasses = array_flip($this->getPersistableClasses($this->objectManager));
        }

        $class = get_class($object);

        if (isset($this->persistableClasses[$class])) {
            $metadata = $this->getMetadata($class);

            $generator = null;
            $generatorType = null;

            // Check if the ID is explicitly set by the user. To avoid the ID to be overridden by the ID generator
            // registered, we disable it for that specific object.
            if ($metadata instanceof ORMClassMetadataInfo) {
                if ($metadata->usesIdGenerator() && false === empty($metadata->getIdentifierValues($object))) {
                    $generator = $metadata->idGenerator;
                    $generatorType = $metadata->generatorType;

                    $metadata->setIdGeneratorType(ORMClassMetadataInfo::GENERATOR_TYPE_NONE);
                    $metadata->setIdGenerator(new ORMAssignedGenerator());
                }
            } else {
                // Do nothing: not supported.
            }

            $this->objectManager->persist($object);

            if (null !== $generator) {
                // Restore the generator if has been temporary unset
                $metadata->setIdGeneratorType($generatorType);
                $metadata->setIdGenerator($generator);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function flush(): void
    {
        $this->objectManager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return string[]
     */
    private function getPersistableClasses(ObjectManager $manager): array
    {
        $persistableClasses = [];
        $allMetadata = $manager->getMetadataFactory()->getAllMetadata();

        foreach ($allMetadata as $metadata) {
            /** @var ORMClassMetadataInfo $metadata */
            if (false === $metadata->isMappedSuperclass
                && false === (isset($metadata->isEmbeddedClass) && $metadata->isEmbeddedClass)
            ) {
                $persistableClasses[] = $metadata->getName();
            }
        }

        return $persistableClasses;
    }

    private function getMetadata(string $class): ClassMetadata
    {
        if (false === array_key_exists($class, $this->metadata)) {
            $classMetadata = $this->objectManager->getClassMetadata($class);
            $this->metadata[$class] = $classMetadata;
        }

        return $this->metadata[$class];
    }
}
