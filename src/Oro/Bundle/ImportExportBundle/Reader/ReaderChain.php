<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

/**
 * The registry that allows to get the batch jobs reader for a specific alias.
 */
class ReaderChain
{
    /** @var ReaderInterface[] [alias => reader, ...] */
    private array $readers = [];

    public function addReader(ReaderInterface $reader, string $alias): void
    {
        $this->readers[$alias] = $reader;
    }

    public function getReader(string $alias): ?ReaderInterface
    {
        return $this->readers[$alias] ?? null;
    }
}
