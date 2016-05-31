<?php

namespace Oro\Bundle\LocaleBundle\Model;

class FallbackType
{
    const SYSTEM        = 'system';
    const PARENT_LOCALE = 'parent_locale';
    const NONE          = null;

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
