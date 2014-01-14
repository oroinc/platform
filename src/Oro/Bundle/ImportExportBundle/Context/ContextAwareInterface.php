<?php

namespace Oro\Bundle\ImportExportBundle\Context;

interface ContextAwareInterface
{
    /**
     * @param ContextInterface $context
     */
    public function setImportExportContext(ContextInterface $context);
}
