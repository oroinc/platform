<?php

namespace Oro\Bundle\LocaleBundle\Model;

/**
 * Defines fallback type constants and provides a value object for fallback type handling.
 *
 * This class encapsulates the different fallback strategies available in the localization system:
 * - SYSTEM: Fall back to system-wide default values
 * - PARENT_LOCALIZATION: Fall back to parent localization values
 * - NONE: No fallback, use only localization-specific values
 */
class FallbackType
{
    const SYSTEM = 'system';
    const PARENT_LOCALIZATION = 'parent_localization';
    const NONE = null;

    /**
     * @var string
     */
    protected $type;

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
