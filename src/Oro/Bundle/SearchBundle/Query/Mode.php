<?php

namespace Oro\Bundle\SearchBundle\Query;

final class Mode
{
    /**
     * Involve only entities of given class
     */
    const NORMAL = 'normal';

    /**
     * Involve entities of given class and descendants
     */
    const WITH_DESCENDANTS = 'with_descendants';

    /**
     * Involve descendants entities for given class
     */
    const ONLY_DESCENDANTS = 'only_descendants';
}
