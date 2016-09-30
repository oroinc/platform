<?php

namespace Oro\Component\Layout;

interface IsApplicableLayoutUpdateInterface
{
    /**
     * @param ContextInterface $context
     * @return bool
     */
    public function isApplicable(ContextInterface $context);
}
