<?php

namespace Oro\Bundle\LocaleBundle\Model;

class FallbackType
{
    public const SYSTEM = 'system';
    public const PARENT_LOCALIZATION = 'parent_localization';
    public const NONE = null;

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
