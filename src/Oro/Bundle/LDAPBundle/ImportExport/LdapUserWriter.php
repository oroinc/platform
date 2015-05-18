<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

class LdapUserWriter implements ItemWriterInterface, ContextAwareInterface
{
    /** @var ContextInterface */
    protected $context;

    /**
     * @param ContextInterface $context
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * Process the supplied data element. Will not be called with any null items
     * in normal operation.
     *
     * @param array $items The list of items to write
     *
     * FIXME: array is not maybe the best structure to hold the items. Investigate this point.
     *
     * @throw InvalidItemException if there is a warning, step execution will continue to the
     * next item.
     * @throws \Exception if there are errors. The framework will catch the
     *                    exception and convert or rethrow it as appropriate.
     */
    public function write(array $items)
    {
        // TODO: Implement write() method.
    }
}