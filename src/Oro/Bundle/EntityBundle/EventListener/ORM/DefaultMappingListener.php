<?php

namespace Oro\Bundle\EntityBundle\EventListener\ORM;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Id\BigIntegerIdentityGenerator;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityBundle\DependencyInjection\OroEntityExtension;

class DefaultMappingListener
{
    /**
     * @var string
     */
    protected $databaseDriver;

    /**
     * @param string $databaseDriver
     */
    public function __construct($databaseDriver)
    {
        $this->databaseDriver = $databaseDriver;
    }

    /**
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        if ($this->databaseDriver !== OroEntityExtension::POSTGRESQL_DB_DRIVER) {
            return;
        }

        /** @var ClassMetadataInfo $classMetadata */
        $classMetadata = $event->getClassMetadata();

        if (!$classMetadata->isIdGeneratorSequence()) {
            return;
        }

        $sequenceGeneratorDefinition = $classMetadata->sequenceGeneratorDefinition;

        if (empty($sequenceGeneratorDefinition)) {
            return;
        }

        $sequenceName = $sequenceGeneratorDefinition['sequenceName'];
        $fieldName    = $classMetadata->getSingleIdentifierFieldName();

        $generator = ($fieldName && $classMetadata->fieldMappings[$fieldName]['type'] === 'bigint')
            ? new BigIntegerIdentityGenerator($sequenceName)
            : new IdentityGenerator($sequenceName);

        $classMetadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $classMetadata->setIdGenerator($generator);
    }
}
