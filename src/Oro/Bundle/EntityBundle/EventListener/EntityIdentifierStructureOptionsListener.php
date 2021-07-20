<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;

/**
 * Adds "identifier" option for identifier fields.
 */
class EntityIdentifierStructureOptionsListener
{
    private const OPTION_NAME = 'identifier';

    /** @var ManagerRegistry */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function onOptionsRequest(EntityStructureOptionsEvent $event)
    {
        $data = $event->getData();
        foreach ($data as $entityStructure) {
            $className = $entityStructure->getClassName();

            $metadata = $this->getMetadataFor($className);
            if (!$metadata) {
                continue;
            }

            $fields = $entityStructure->getFields();
            foreach ($fields as $field) {
                if ($metadata->isIdentifier($field->getName())) {
                    $field->addOption(self::OPTION_NAME, true);
                }
            }
        }
        $event->setData($data);
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
