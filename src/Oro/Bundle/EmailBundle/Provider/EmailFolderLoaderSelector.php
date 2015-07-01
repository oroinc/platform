<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

class EmailFolderLoaderSelector
{
    /**
     * @var EmailFolderLoaderInterface[]
     */
    private $loaders = array();

    /**
     * Adds implementation of EmailFolderLoaderInterface
     *
     * @param EmailFolderLoaderInterface $loader
     */
    public function addLoader(EmailFolderLoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * Gets implementation of EmailFolderLoaderInterface for the given email origin
     *
     * @param EmailOrigin $origin
     * @return EmailFolderLoaderInterface
     * @throws \RuntimeException
     */
    public function select(EmailOrigin $origin)
    {
        foreach ($this->loaders as $loader) {
            if ($loader->supports($origin)) {
                return $loader;
            }
        }

        throw new \RuntimeException(sprintf('Cannot find an email folder loader. Origin id: %d.', $origin->getId()));
    }
}
