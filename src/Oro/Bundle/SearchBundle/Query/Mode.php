<?php

namespace Oro\Bundle\SearchBundle\Query;

final class Mode
{
    /**
     * Involve only entities of given class
     */
    public const NORMAL = 'normal';

    /**
     * Involve entities of given class and descendants
     */
    public const WITH_DESCENDANTS = 'with_descendants';

    /**
     * Involve descendants entities for given class
     */
    public const ONLY_DESCENDANTS = 'only_descendants';
}
