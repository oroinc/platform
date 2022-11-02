<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions;

use Oro\Component\PhpUtils\ClassGenerator;

/**
 * Abstract class for schema-based entity code generators.
 */
abstract class AbstractEntityGeneratorExtension
{
    /**
     * Should return true if this extension needs to be involved in PHP code generation.
     */
    abstract public function supports(array $schema): bool;

    /**
     * This method can be used to make modifications to the generated PHP code.
     */
    abstract public function generate(array $schema, ClassGenerator $class): void;
}
