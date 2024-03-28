<?php

namespace Oro\Bundle\ThemeBundle\Validator;

use Oro\Component\Config\CumulativeResourceInfo;

/**
 * Theme configuration validator service interface.
 *
 * Services which will implements this interface will validates theme configuration files theme.yml.
 */
interface ConfigurationValidatorInterface
{
    public function supports(CumulativeResourceInfo $resource): bool;

    /**
     * The method validates the theme configuration.
     *
     * Returns a list of messages if the file is not relevant
     * Example: ['error message', 'not valid message', ...]
     *
     * @param CumulativeResourceInfo[] $resources
     */
    public function validate(iterable $resources): iterable;
}
