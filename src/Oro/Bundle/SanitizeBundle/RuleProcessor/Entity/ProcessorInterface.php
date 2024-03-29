<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Entity sanitizing rule processor interface.
 */
interface ProcessorInterface
{
    public static function getProcessorName(): string;

    /**
     * return string[]
     */
    public function getSqls(ClassMetadata $metadata, array $sanitizeRuleOptions = []): array;
}
