<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

/**
 * Adds "exclude" option for excluded entities and fields.
 */
class EntityExclusionStructureOptionsListener
{
    private const OPTION_NAME = 'exclude';

    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var ExclusionProviderInterface */
    private $exclusionProvider;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ExclusionProviderInterface $exclusionProvider
     */
    public function __construct(ManagerRegistry $managerRegistry, ExclusionProviderInterface $exclusionProvider)
    {
        $this->managerRegistry = $managerRegistry;
        $this->exclusionProvider = $exclusionProvider;
    }

    /**
     * @param EntityStructureOptionsEvent $event
     */
    public function onOptionsRequest(EntityStructureOptionsEvent $event)
    {
        $data = $event->getData();
        foreach ($data as $entityStructure) {
            $className = $entityStructure->getClassName();
            if ($this->exclusionProvider->isIgnoredEntity($className)) {
                $entityStructure->addOption(self::OPTION_NAME, true);
                continue;
            }

            $metadata = $this->getMetadataFor($className);
            if (!$metadata) {
                continue;
            }

            $this->processFields($entityStructure->getFields(), $metadata);
        }
        $event->setData($data);
    }


    /**
     * @param array|EntityFieldStructure[] $fields
     * @param ClassMetadata $entityClassMetadata
     */
    private function processFields(array $fields, ClassMetadata $entityClassMetadata)
    {
        foreach ($fields as $field) {
            $fieldName = $field->getName();
            if (UnidirectionalFieldHelper::isFieldUnidirectional($fieldName)) {
                $realFieldName = UnidirectionalFieldHelper::getRealFieldName($fieldName);
                $realFieldClass = UnidirectionalFieldHelper::getRealFieldClass($fieldName);
                $fieldClassMetadata = $this->getMetadataFor($realFieldClass);
                if (!$fieldClassMetadata) {
                    continue;
                }
            } else {
                $realFieldName = $fieldName;
                $fieldClassMetadata = $entityClassMetadata;
            }
            $relatedEntity = $field->getRelatedEntityName();
            if (($relatedEntity && $this->exclusionProvider->isIgnoredEntity($relatedEntity)) ||
                $this->exclusionProvider->isIgnoredField($fieldClassMetadata, $realFieldName)
            ) {
                $field->addOption(self::OPTION_NAME, true);
            }
        }
    }

    /**
     * @param string $className
     *
     * @return ClassMetadata
     */
    private function getMetadataFor($className)
    {
        $manager = $this->managerRegistry->getManagerForClass($className);

        return $manager ? $manager->getClassMetadata($className) : null;
    }
}
