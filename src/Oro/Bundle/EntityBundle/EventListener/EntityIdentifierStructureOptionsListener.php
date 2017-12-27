<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityStructure;

class EntityIdentifierStructureOptionsListener
{
    const OPTION_NAME = 'identifier';

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param EntityStructureOptionsEvent $event
     */
    public function onOptionsRequest(EntityStructureOptionsEvent $event)
    {
        $data = $event->getData();

        foreach ($data as $entityStructure) {
            if (!$entityStructure instanceof EntityStructure) {
                continue;
            }

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
    protected function getMetadataFor($className)
    {
        $manager = $this->managerRegistry->getManagerForClass($className);

        return $manager ? $manager->getClassMetadata($className) : null;
    }
}
