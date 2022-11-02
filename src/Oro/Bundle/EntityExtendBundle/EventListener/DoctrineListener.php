<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Annotation\ORM\DiscriminatorValue;
use Oro\Bundle\EntityExtendBundle\ORM\ExtendMetadataBuilder;

/**
 * Enriches discriminator mapping and field defaults in class metadata.
 */
class DoctrineListener
{
    private ExtendMetadataBuilder $metadataBuilder;
    private Reader $annotationReader;
    private ConfigProvider $extendConfigProvider;
    private array $collectedMaps = [];
    private array $collectedValues = [];

    public function __construct(
        ExtendMetadataBuilder $metadataBuilder,
        Reader $reader,
        ConfigProvider $extendConfigProvider
    ) {
        $this->metadataBuilder = $metadataBuilder;
        $this->annotationReader = $reader;
        $this->extendConfigProvider = $extendConfigProvider;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $classMetadata = $event->getClassMetadata();
        $className = $classMetadata->getName();

        if ($this->metadataBuilder->supports($className)) {
            $classMetadataBuilder = new ClassMetadataBuilder($classMetadata);
            $this->metadataBuilder->build($classMetadataBuilder, $className);
            $event->getEntityManager()
                ->getMetadataFactory()
                ->setMetadataFor($className, $classMetadata);
        }

        $this->processDiscriminatorValues($classMetadata, $event->getObjectManager());
        $this->processFieldMappings($classMetadata);
    }

    /**
     * Collects discriminator map entries from child classes for entities with inheritance.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processDiscriminatorValues(ClassMetadata $class, EntityManagerInterface $em): void
    {
        if ($class->isInheritanceTypeNone()) {
            return;
        }

        if ($class->isRootEntity()) {
            $allClasses = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
            $className = $class->getName();
            $map = $class->discriminatorMap ?: [];

            $duplicates = [];
            foreach ($allClasses as $subClassCandidate) {
                if (is_subclass_of($subClassCandidate, $className) && !\in_array($subClassCandidate, $map, true)) {
                    $value = $this->getDiscriminatorValue($em->getMetadataFactory(), $subClassCandidate);

                    if (null !== $value) {
                        if (isset($map[$value])) {
                            $duplicates[] = $value;
                        }

                        $map[$value] = $subClassCandidate;
                    }
                }
            }

            if ($duplicates) {
                throw MappingException::duplicateDiscriminatorEntry($class->getName(), $duplicates, $map);
            }

            $class->setDiscriminatorMap($map);
            $this->collectedMaps = array_merge($this->collectedMaps, array_fill_keys(array_values($map), $map));
        } elseif (isset($this->collectedMaps[$class->name])
            && $class->discriminatorMap !== $this->collectedMaps[$class->name]
        ) {
            $class->setDiscriminatorMap($this->collectedMaps[$class->name]);
        }
    }

    private function getDiscriminatorValue(ClassMetadataFactory $factory, string $entityClass): mixed
    {
        if (!\array_key_exists($entityClass, $this->collectedValues)) {
            $value = null;

            if ($factory->hasMetadataFor($entityClass)) {
                /** @var ClassMetadata $metadata */
                $metadata = $factory->getMetadataFor($entityClass);
                $value = $metadata->discriminatorValue;
            } else {
                $annotation = $this->annotationReader->getClassAnnotation(
                    new \ReflectionClass($entityClass),
                    DiscriminatorValue::class
                );
                if ($annotation instanceof DiscriminatorValue) {
                    $value = $annotation->getValue();
                }
            }

            $this->collectedValues[$entityClass] = $value;
        }

        return $this->collectedValues[$entityClass];
    }

    private function processFieldMappings(ClassMetadata $classMetadata): void
    {
        $className = $classMetadata->getName();

        foreach ($classMetadata->fieldMappings as $fieldName => $mapping) {
            if (!$this->extendConfigProvider->hasConfig($className, $fieldName)) {
                continue;
            }

            $fieldConfig = $this->extendConfigProvider->getConfig($className, $fieldName);

            if (!$fieldConfig->has('default') || $fieldConfig->get('default') === null) {
                continue;
            }

            $classMetadata->setAttributeOverride(
                $fieldName,
                array_merge(
                    $mapping,
                    [
                        'default' => $fieldConfig->get('default')
                    ]
                )
            );
        }
    }
}
