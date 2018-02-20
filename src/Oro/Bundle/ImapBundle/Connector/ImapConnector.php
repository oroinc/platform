<?php

namespace Oro\Bundle\ImapBundle\Connector;

use Oro\Bundle\ImapBundle\Connector\Search\SearchQuery;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryBuilder;
use Oro\Bundle\ImapBundle\Connector\Search\SearchStringManagerInterface;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;
use Oro\Bundle\ImapBundle\Mail\Storage\Imap;
use Oro\Bundle\ImapBundle\Mail\Storage\Message;

/**
 * A base class for connectors intended to work with email's servers through IMAP protocol.
 */
class ImapConnector
{
    /**
     * @var ImapConfig
     */
    protected $config;

    /**
     * @var ImapServicesFactory
     */
    protected $factory;

    /**
     * @var Imap
     */
    protected $imap;

    /**
     * @var SearchStringManagerInterface
     */
    protected $searchStringManager;

    /**
     * @param ImapConfig          $config
     * @param ImapServicesFactory $factory
     */
    public function __construct(ImapConfig $config, ImapServicesFactory $factory)
    {
        $this->config  = $config;
        $this->factory = $factory;
        $this->imap    = null;
    }

    /**
     * Gets capabilities of IMAP server
     *
     * @return string[] list of capabilities
     */
    public function getCapability()
    {
        $this->ensureConnected();

        return $this->imap->capability();
    }

    /**
     * Gets the search query builder
     *
     * @return SearchQueryBuilder
     */
    public function getSearchQueryBuilder()
    {
        $this->ensureConnected();

        return new SearchQueryBuilder(new SearchQuery($this->searchStringManager));
    }

    /**
     * Get selected folder
     *
     * @return string
     */
    public function getSelectedFolder()
    {
        $this->ensureConnected();

        return (string)$this->imap->getCurrentFolder();
    }

    /**
     * Set selected folder
     *
     * @param string $folder
     */
    public function selectFolder($folder)
    {
        $this->ensureConnected();

        $this->imap->selectFolder($folder);
    }

    /**
     * @param SearchQuery|null $query
     *
     * @return ImapMessageIterator
     */
    public function findItems($query = null)
    {
        $this->ensureConnected();

        $searchString = '';
        if ($query !== null) {
            $searchString = $query->convertToSearchString();
        }

        if (empty($searchString)) {
            $result = new ImapMessageIterator($this->imap);
        } else {
            $ids    = $this->imap->search([$searchString]);
            $result = new ImapMessageIterator($this->imap, $ids);
        }

        return $result;
    }

    /**
     * @param int|null $lastSyncId
     *
     * @return ImapMessageIterator
     */
    public function findItemsUidBased($lastSyncId = null)
    {
        $this->ensureConnected();

        if (empty($lastSyncId)) {
            $result = new ImapMessageIterator($this->imap);
        } else {
            $uids   = $this->imap->getLastMessageIdsFromId($lastSyncId);
            $result = new ImapMessageIterator($this->imap, $uids, true);
        }

        return $result;
    }

    /**
     * @param null $query
     *
     * @return mixed
     */
    public function findUIDs($query = null)
    {
        $this->ensureConnected();

        return $this->imap->uidSearch([$query]);
    }

    /**
     * Finds folders.
     *
     * @param string|null $parentFolder The global name of a parent folder.
     * @param bool        $recursive    True to get all subordinate folders
     *
     * @return Folder[]
     */
    public function findFolders($parentFolder = null, $recursive = false)
    {
        $this->ensureConnected();

        return $this->getSubFolders($this->imap->getFolders($parentFolder), $recursive);
    }

    /**
     * Finds a folder by its name.
     *
     * @param string $name The global name of the folder.
     *
     * @return Folder
     */
    public function findFolder($name)
    {
        $this->ensureConnected();

        return $this->imap->getFolders($name);
    }

    /**
     * Retrieves item detail by its id.
     *
     * @param int $uid The UID of a message
     *
     * @return Message
     */
    public function getItem($uid)
    {
        $this->ensureConnected();

        $id = $this->imap->getNumberByUniqueId($uid);

        return $this->imap->getMessage($id);
    }

    /**
     * Gets UIDVALIDITY of currently selected folder
     *
     * @return int
     */
    public function getUidValidity()
    {
        $this->ensureConnected();

        return $this->imap->getUidValidity();
    }

    /**
     * Set flags for massage in origin
     *
     * @param string $uid   - The UID of a message
     * @param array  $flags - array of flags
     *
     * @return $this;
     */
    public function setFlags($uid, $flags)
    {
        $this->ensureConnected();

        $id = $this->imap->getNumberByUniqueId($uid);
        $this->imap->setFlags($id, $flags);

        return $this;
    }

    /**
     * Makes sure that there is active connection to IMAP server
     */
    protected function ensureConnected()
    {
        if ($this->imap === null) {
            $imapServices              = $this->factory->createImapServices($this->config);
            $this->imap                = $imapServices->getStorage();
            $this->searchStringManager = $imapServices->getSearchStringManager();
        }
    }

    /**
     * Gets sub folders.
     *
     * @param Folder $parentFolder The parent folder.
     * @param bool   $recursive    Determines whether child folders should be returned as well
     *
     * @return Folder[]
     */
    protected function getSubFolders(Folder $parentFolder, $recursive = false)
    {
        $result = [];

        $parentFolder->guessFolderType();

        /** @var Folder $folder */
        foreach ($parentFolder as $folder) {
            $result[] = $folder;

            if ($recursive) {
                $folder->type = $parentFolder->type;

                $result = array_merge($result, $this->getSubFolders($folder, $recursive));
            }
        }

        return $result;
    }
}
