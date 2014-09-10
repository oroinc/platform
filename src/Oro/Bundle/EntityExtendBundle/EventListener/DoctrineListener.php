<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\MultiEnumManager;
use Oro\Bundle\EntityExtendBundle\ORM\ExtendMetadataBuilder;

class DoctrineListener
{
    /** @var ServiceLink */
    protected $metadataBuilderServiceLink;

    /** @var MultiEnumManager */
    protected $multiEnumManager;

    /**
     * @param ServiceLink      $metadataBuilderServiceLink The link to ExtendMetadataBuilder
     * @param MultiEnumManager $multiEnumManager
     */
    public function __construct(ServiceLink $metadataBuilderServiceLink, MultiEnumManager $multiEnumManager)
    {
        $this->metadataBuilderServiceLink = $metadataBuilderServiceLink;
        $this->multiEnumManager           = $multiEnumManager;
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

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $this->multiEnumManager->handleOnFlush($event);
    }
}
