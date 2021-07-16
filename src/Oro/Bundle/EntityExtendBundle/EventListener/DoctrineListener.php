<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Annotation\ORM\DiscriminatorValue;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\MultiEnumManager;
use Oro\Bundle\EntityExtendBundle\ORM\ExtendMetadataBuilder;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Enriches discriminator mapping and field defaults in class metadata, ensures multi-enums are properly flushed.
 */
class DoctrineListener
{
    const ANNOTATION_DISCRIMINATOR_VALUE = 'Oro\\Bundle\\EntityExtendBundle\\Annotation\\ORM\\DiscriminatorValue';

    /** @var ServiceLink */
    protected $metadataBuilderServiceLink;

    /** @var MultiEnumManager */
    protected $multiEnumManager;

    /** @var array */
    protected $collectedMaps = [];

    /** @var array */
    protected $collectedValues = [];

    /** @var Reader */
    protected $annotationReader;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param ServiceLink      $metadataBuilderLink The link to ExtendMetadataBuilder
     * @param MultiEnumManager $multiEnumManager
     * @param Reader           $reader
     * @param ConfigProvider   $extendConfigProvider
     */
    public function __construct(
        ServiceLink $metadataBuilderLink,
        MultiEnumManager $multiEnumManager,
        Reader $reader,
        ConfigProvider $extendConfigProvider
    ) {
        $this->metadataBuilderServiceLink = $metadataBuilderLink;
        $this->multiEnumManager           = $multiEnumManager;
        $this->annotationReader           = $reader;
        $this->extendConfigProvider       = $extendConfigProvider;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $classMetadata = $event->getClassMetadata();
        $className     = $classMetadata->getName();

        /** @var ExtendMetadataBuilder $metadataBuilder */
        $metadataBuilder = $this->metadataBuilderServiceLink->getService();
        if ($metadataBuilder->supports($className)) {
            $classMetadataBuilder = new ClassMetadataBuilder($classMetadata);
            $metadataBuilder->build($classMetadataBuilder, $className);
            $event->getEntityManager()
                ->getMetadataFactory()
                ->setMetadataFor($className, $classMetadata);
        }

        $this->processDiscriminatorValues($classMetadata, $event->getObjectManager());
        $this->processFieldMappings($classMetadata);
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        $this->multiEnumManager->handleOnFlush($event);
    }

    /**
     * Collecting discriminator map entries from child classes for entities with inheritance not equals NONE
     *
     * @throws MappingException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processDiscriminatorValues(ClassMetadata $class, EntityManager $em)
    {
        if (!$class->isInheritanceTypeNone()) {
            if ($class->isRootEntity()) {
                $allClasses = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
                $FQCN       = $class->getName();
                $map        = $class->discriminatorMap ?: [];

                $duplicates = [];
                foreach ($allClasses as $subClassCandidate) {
                    if (is_subclass_of($subClassCandidate, $FQCN) && !in_array($subClassCandidate, $map, true)) {
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
    }

    /**
     * @param ClassMetadataFactory $factory
     * @param string               $entityFQCN Class name
     *
     * @return mixed
     */
    protected function getDiscriminatorValue(ClassMetadataFactory $factory, $entityFQCN)
    {
        if (!array_key_exists($entityFQCN, $this->collectedValues)) {
            $value = null;

            if ($factory->hasMetadataFor($entityFQCN)) {
                /** @var ClassMetadata $metadata */
                $metadata = $factory->getMetadataFor($entityFQCN);
                $value    = $metadata->discriminatorValue;
            } else {
                $ref        = new \ReflectionClass($entityFQCN);
                $annotation = $this->annotationReader->getClassAnnotation($ref, self::ANNOTATION_DISCRIMINATOR_VALUE);

                if ($annotation instanceof DiscriminatorValue) {
                    $value = $annotation->getValue();
                }
            }

            $this->collectedValues[$entityFQCN] = $value;
        }

        return $this->collectedValues[$entityFQCN];
    }

    protected function processFieldMappings(ClassMetadata $classMetadata)
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
