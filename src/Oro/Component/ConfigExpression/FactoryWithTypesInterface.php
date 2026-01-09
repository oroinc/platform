<?php

namespace Oro\Component\ConfigExpression;

/**
 * Defines the contract for factories that can report available expression types.
 *
 * This interface extends factory capabilities to include type discovery and validation.
 * Implementations provide methods to retrieve all available expression type names and to check
 * whether a specific expression type is available, enabling runtime type validation and discovery.
 */
interface FactoryWithTypesInterface
{
    /**
     * @return string[]
     */
    public function getTypes();

    /**
     * @param string $name
     * @return bool
     */
    public function isTypeExists($name);
}
