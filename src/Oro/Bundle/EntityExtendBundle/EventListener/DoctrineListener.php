<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityExtendBundle\ORM\ExtendMetadataBuilder;

class DoctrineListener
{
    /** @var ServiceLink */
    protected $metadataBuilderServiceLink;

    /**
     * @param ServiceLink $metadataBuilderServiceLink The link to ExtendMetadataBuilder
     */
    public function __construct(ServiceLink $metadataBuilderServiceLink)
    {
        $this->metadataBuilderServiceLink = $metadataBuilderServiceLink;
    }

    /**
     * @param LoadClassMetadataEventArgs $event
     */
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
    }
}
