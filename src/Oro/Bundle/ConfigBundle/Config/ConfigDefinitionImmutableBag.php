<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

/**
 * Immutable container for system configuration definitions.
 *
 * Extends Symfony's {@see FrozenParameterBag} to provide a read-only parameter bag
 * specifically designed for storing system configuration definitions. This ensures
 * that configuration definitions cannot be modified after initialization, maintaining
 * data integrity throughout the application lifecycle.
 */
class ConfigDefinitionImmutableBag extends FrozenParameterBag
{
}
