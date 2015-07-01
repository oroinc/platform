<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Provider\EmailFolderLoaderSelector;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;

/**
 * This class responsible for binging EmailAddress to owner entities
 */
class EmailFolderManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EmailFolderLoaderSelector */
    protected $selector;

    /**
     * Constructor.
     *
     * @param EmailFolderLoaderSelector $selector
     */
    public function __construct(EmailFolderLoaderSelector $selector)
    {
        $this->selector = $selector;
    }

    /**
     * Get  all email folders
     *
     * @param EmailOrigin $origin
     *
     * @return Folder[]
     * @ throws LoadEmailFolderException
     * @ throws LoadEmailFolderFailedException
     * @ throws \Exception
     */
    public function getEmailFolders(EmailOrigin $origin)
    {
        $folders = array();
        try {
            $loader = $this->selector->select($origin);
            $folders = $loader->loadEmailFolders($origin);
        } catch (\RuntimeException $loadEx) {
            $this->logger->notice(
                sprintf('Load email folder failed. Email id: %d. Error: %s', $origin->getId(), $loadEx->getMessage()),
                ['exception' => $loadEx]
            );
        } catch (\Exception $ex) {
            $this->logger->warning(
                sprintf('Load email folder failed. Email id: %d. Error: %s.', $origin->getId(), $ex->getMessage()),
                ['exception' => $ex]
            );
        }

        return $folders;
    }
}
