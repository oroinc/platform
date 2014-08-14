<?php

namespace Oro\Bundle\EntityBundle\EventListener\ORM;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Id\BigIntegerIdentityGenerator;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;

class GeneratedValueStrategyListener
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
        if ($this->databaseDriver !== DatabaseDriverInterface::DRIVER_POSTGRESQL) {
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
        $fieldMapping = $classMetadata->getFieldMapping($fieldName);

        $generator = ($fieldName && $fieldMapping['type'] === Type::BIGINT)
            ? new BigIntegerIdentityGenerator($sequenceName)
            : new IdentityGenerator($sequenceName);

        $classMetadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $classMetadata->setIdGenerator($generator);
    }
}
