<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Oro\Bundle\ImportExportBundle\Reader\ReaderInterface;

class LdapUserReader implements ReaderInterface {

    /**
     * Reads a piece of input data and advance to the next one. Implementations
     * <strong>must</strong> return <code>null</code> at the end of the input
     * data set.
     *
     * @throws InvalidItemException if there is a problem reading the current record
     *                              (but the next one may still be valid)
     * @throws \Exception           if an there is a non-specific error. (step execution will
     *                              be stopped in that case)
     *
     * @return null|mixed
     */
    public function read()
    {
        // TODO: Implement read() method.
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        // TODO: Implement setStepExecution() method.
    }
}