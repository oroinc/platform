<?php

namespace Oro\Bundle\EntityBundle\EventListener\ORM;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Id\BigIntegerIdentityGenerator;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Force IDENTITY strategy for PostgreSQL sequence generators.
 */
class GeneratedValueStrategyListener
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
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
        $fieldMapping = $classMetadata->getFieldMapping($fieldName);

        $generator = ($fieldName && $fieldMapping['type'] === Types::BIGINT)
            ? new BigIntegerIdentityGenerator($sequenceName)
            : new IdentityGenerator($sequenceName);

        $classMetadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $classMetadata->setIdGenerator($generator);
    }
}
