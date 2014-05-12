<?php

namespace Oro\Bundle\BatchBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Batch Bundle
 *
 */
class OroBatchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'AkeneoBatchBundle';
    }
}
