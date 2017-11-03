<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

class EntityExclusionStructureOptionsListener
{
    const OPTION_NAME = 'exclude';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ExclusionProviderInterface */
    protected $exclusionProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ExclusionProviderInterface $exclusionProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, ExclusionProviderInterface $exclusionProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->exclusionProvider = $exclusionProvider;
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
            $entityStructure->addOption(self::OPTION_NAME, $this->exclusionProvider->isIgnoredEntity($className));

            $metadata = $this->getMetadataFor($className);
            if (!$metadata) {
                continue;
            }

            $fields = $entityStructure->getFields();
            foreach ($fields as $field) {
                $field->addOption(
                    self::OPTION_NAME,
                    $this->exclusionProvider->isIgnoredField($metadata, $field->getName())
                );
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
        $manager = $this->doctrineHelper->getEntityManager($className, false);

        return $manager ? $manager->getMetadataFactory()->getMetadataFor($className) : null;
    }
}
