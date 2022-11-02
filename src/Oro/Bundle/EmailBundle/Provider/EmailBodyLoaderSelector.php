<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

/**
 * Provides a way to find an email body loader for an email origin.
 */
class EmailBodyLoaderSelector
{
    /** @var iterable|EmailBodyLoaderInterface[] */
    private $loaders;

    /**
     * @param iterable|EmailBodyLoaderInterface[] $loaders
     */
    public function __construct(iterable $loaders)
    {
        $this->loaders = $loaders;
    }

    /**
     * Gets an email body loader for the given email origin.
     *
     * @param EmailOrigin $origin
     *
     * @return EmailBodyLoaderInterface
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

        throw new \RuntimeException(sprintf('Cannot find an email body loader. Origin id: %d.', $origin->getId()));
    }
}
