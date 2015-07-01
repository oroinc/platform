<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Zend\Mail\Storage\Exception as MailException;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;

/**
 * Class ImapEmailFolderManager
 *
 * @package Oro\Bundle\ImapBundle\Manager
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ImapEmailFolderManager
{
    /** @var ImapConnector */
    protected $connector;

    /**
     * @param ImapConnector $connector
     */
    public function __construct(ImapConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Retrieve folders
     *
     * @param string|null $parentFolder The global name of a parent folder.
     * @param bool $recursive True to get all subordinate folders
     *
     * @return Folder[]
     */
    public function getFolders($parentFolder = null, $recursive = false)
    {
        return $this->connector->findFolders($parentFolder, $recursive);
    }

}
