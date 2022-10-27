<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

/**
 * Provides a way to find an email flags manager loader for an email origin.
 */
class EmailFlagManagerLoaderSelector
{
    /** @var iterable|EmailFlagManagerLoaderInterface[] */
    private $loaders;

    /**
     * @param iterable|EmailFlagManagerLoaderInterface[] $loaders
     */
    public function __construct(iterable $loaders)
    {
        $this->loaders = $loaders;
    }

    /**
     * Gets an email flags manager loader for the given email origin.
     *
     * @param EmailOrigin $origin
     *
     * @return EmailFlagManagerLoaderInterface
     *
     * @throws \RuntimeException
     */
    public function select(EmailOrigin $origin)
    {
        foreach ($this->loaders as $loader) {
            if ($loader->supports($origin)) {
                return $loader;
            }
        }

        throw new \RuntimeException(
            sprintf('Cannot find an email flag manager loader. Origin id: %d.', $origin->getId())
        );
    }
}
