<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

/**
 * Class EmailFlagManagerLoaderSelector
 * @package Oro\Bundle\EmailBundle\Provider
 */
class EmailFlagManagerLoaderSelector
{
    /**
     * @var EmailFlagManagerLoaderInterface[]
     */
    private $loaders = array();

    /**
     * Adds implementation of EmailBodyLoaderInterface
     *
     * @param EmailFlagManagerLoaderInterface $loader - entity implemented
     * EmailFlagManagerLoaderInterface
     *
     * @return void
     */
    public function addLoader(EmailFlagManagerLoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * Gets implementation of EmailBodyLoaderInterface for the given email origin
     *
     * @param EmailOrigin $origin - entity EmailOrigin
     *
     * @return EmailFlagManagerLoaderInterface
     * @throws \RuntimeException
     */
    public function select(EmailOrigin $origin)
    {
        foreach ($this->loaders as $loader) {
            if ($loader->supports($origin)) {
                return $loader;
            }
        }

        throw new \RuntimeException($this->getErrorMessage($origin));
    }

    /**
     * Return test for error message
     *
     * @param EmailOrigin $origin - entity EmailOrigin
     *
     * @return string
     */
    protected function getErrorMessage(EmailOrigin $origin)
    {
        $message = 'Cannot find an email flag manager loader. Origin id: %d.';

        return sprintf($message, $origin->getId());
    }
}
