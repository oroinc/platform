<?php

namespace Oro\Bundle\ImportExportBundle\Context;

interface ContextAwareInterface
{
    public function setImportExportContext(ContextInterface $context);
}
