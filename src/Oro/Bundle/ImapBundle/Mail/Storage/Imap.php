<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage;

use Oro\Bundle\ImapBundle\Mail\Protocol\Imap as ProtocolImap;

class Imap extends \Zend\Mail\Storage\Imap
{
    const BODY_HEADER      = 'BODY[HEADER]';
    const BODY_PEEK_HEADER = 'BODY.PEEK[HEADER]';
    const BODY_PEEK_TEXT   = 'BODY.PEEK[TEXT]';
    const RFC822_HEADER    = 'RFC822.HEADER';
    const FLAGS            = 'FLAGS';
    const UID              = 'UID';
    const INTERNALDATE     = 'INTERNALDATE';

    /**
     * Indicates whether IMAP server can store the same message in different folders
     */
    const CAPABILITY_MSG_MULTI_FOLDERS = 'X_MSG_MULTI_FOLDERS';

    /**
     * UIDVALIDITY of currently selected folder
     *
     * @var int
     */
    protected $uidValidity;

    /**
     * Items to be returned by getMessage
     *
     * @var array
     */
    protected $getMessageItems;

    /**
     * A local cache of IMAP server capabilities
     *
     * @var array
     */
    private $capability;

    /**
     * This flag is used to prevent closing the default storage socket
     * There is only one case when this flag set to true, when the storage is created
     * based on another storage and we want to use the already opened socket of the base storage
     * See the constructor of this class for details
     *
     * @var bool
     */
    private $ignoreCloseCommand = false;

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function __construct($params)
    {
        if ($params instanceof Imap) {
            $params->ignoreCloseCommand = true;
            $this->currentFolder = $params->currentFolder;
            $params = $params->protocol;
        }

        if (is_array($params)) {
            $params = (object) $params;
        }

        $this->has['flags'] = true;

        if ($params instanceof ProtocolImap) {
            $this->protocol = $params;
            try {
                $this->selectFolder('INBOX');
            } catch (Exception\ExceptionInterface $e) {
                throw new Exception\RuntimeException('cannot select INBOX, is this a valid transport?', 0, $e);
            }
            $this->postInit();

            return;
        }

        if (!isset($params->user)) {
            throw new Exception\InvalidArgumentException('need at least user in params');
        }

        $host     = isset($params->host)     ? $params->host     : 'localhost';
        $password = isset($params->password) ? $params->password : '';
        $port     = isset($params->port)     ? $params->port     : null;
        $ssl      = isset($params->ssl)      ? $params->ssl      : false;

        $this->protocol = new ProtocolImap();
        $this->protocol->connect($host, $port, $ssl);
        if (!$this->protocol->login($params->user, $password)) {
            throw new Exception\RuntimeException('cannot login, user or password wrong');
        }
        $this->selectFolder(isset($params->folder) ? $params->folder : 'INBOX');

        $this->postInit();
    }

    protected function postInit()
    {
        $this->messageClass = 'Oro\Bundle\ImapBundle\Mail\Storage\Message';
        $this->getMessageItems = array(
            self::FLAGS,
            self::BODY_PEEK_HEADER,
            self::UID,
            self::INTERNALDATE
        );
    }

    /**
     * Get capabilities from IMAP server
     *
     * @return string[] list of capabilities
     */
    public function capability()
    {
        if ($this->capability === null) {
            $this->capability = $this->getCapability();
        }

        return $this->capability;
    }

    /**
     * Gets UIDVALIDITY of currently selected folder
     *
     * @return int
     */
    public function getUidValidity()
    {
        return $this->uidValidity;
    }

    /**
     * get root folder or given folder
     *
     * @param  string $rootFolder get folder structure for given folder, else root
     * @return Folder root or wanted folder
     * @throws \Zend\Mail\Storage\Exception\RuntimeException
     * @throws \Zend\Mail\Storage\Exception\InvalidArgumentException
     * @throws \Zend\Mail\Protocol\Exception\RuntimeException
     */
    public function getFolders($rootFolder = null)
    {
        $folders = $this->protocol->listMailbox((string)$rootFolder);
        if (!$folders) {
            throw new \Zend\Mail\Storage\Exception\InvalidArgumentException('folder not found');
        }

        $decodedFolders = array();
        foreach ($folders as $globalName => $data) {
            $decodedGlobalName = mb_convert_encoding($globalName, 'UTF-8', 'UTF7-IMAP');
            $decodedFolders[$decodedGlobalName] = $data;
        }
        $folders = $decodedFolders;

        ksort($folders, SORT_STRING);
        $root = new Folder('/', '/', false);
        $stack = array(null);
        $folderStack = array(null);
        $parentFolder = $root;
        $parent = '';

        foreach ($folders as $globalName => $data) {
            do {
                if (!$parent || strpos($globalName, $parent) === 0) {
                    // build local name based on global name
                    $lastDelimPosition = strrpos($globalName, $data['delim']);
                    if ($lastDelimPosition === false) {
                        $localName = $globalName;
                    } else {
                        $localName = substr($globalName, $lastDelimPosition + 1);
                    }

                    $selectable = !$data['flags'] || !in_array('\\Noselect', $data['flags']);

                    array_push($stack, $parent);
                    $parent = $globalName . $data['delim'];
                    $folder = new Folder($localName, $globalName, $selectable);
                    $folder->setFlags(!isset($data['flags']) ? array() : $data['flags']);
                    $this->postInitFolder($folder);
                    $parentFolder->$localName = $folder;
                    array_push($folderStack, $parentFolder);
                    $parentFolder = $folder;
                    break;
                } elseif ($stack) {
                    $parent = array_pop($stack);
                    $parentFolder = array_pop($folderStack);
                }
            } while ($stack);
            if (!$stack) {
                throw new \Zend\Mail\Storage\Exception\RuntimeException('error while constructing folder tree');
            }
        }

        return $root;
    }

    /**
     * Does a folder post initialization actions
     *
     * @param Folder $folder
     */
    protected function postInitFolder(Folder $folder)
    {
        if (strtoupper($folder->getGlobalName()) === 'INBOX') {
            if (!$folder->hasFlag(Folder::FLAG_INBOX)) {
                $folder->addFlag(Folder::FLAG_INBOX);
            }
        }
        if ($folder->hasFlag('Junk')) {
            $folder->addFlag(Folder::FLAG_SPAM);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage($id)
    {
        return $this->createMessageObject(
            $id,
            $this->protocol->fetch($this->getMessageItems, $id)
        );
    }

    /**
     * Get a messages with headers and body
     *
     * @param int[] $ids int numbers of messages
     *
     * @return Message[] key = message id
     */
    public function getMessages($ids)
    {
        $messages = [];

        $items = $this->protocol->fetch($this->getMessageItems, $ids);
        foreach ($items as $id => $data) {
            $messages[$id] = $this->createMessageObject($id, $data);
        }

        return $messages;
    }

    /**
     * Searches messages by the given criteria
     *
     * @param array $criteria The search criteria
     * @return string[] Message ids
     * @throws \Zend\Mail\Storage\Exception\RuntimeException
     */
    public function search(array $criteria)
    {
        if (empty($criteria)) {
            throw new \Zend\Mail\Storage\Exception\RuntimeException('The search criteria must not be empty.');
        }

        $response = $this->protocol->search($criteria);
        if (!is_array($response)) {
            throw new \Zend\Mail\Storage\Exception\RuntimeException('Cannot search messages.');
        }

        return $response;
    }

    /**
     * Searches UIDS by the given criteria
     *
     * @param array $criteria
     *
     * @return mixed
     */
    public function uidSearch(array $criteria)
    {
        if (empty($criteria)) {
            throw new \Zend\Mail\Storage\Exception\RuntimeException('The search criteria must not be empty.');
        }

        $response = $this->protocol->requestAndResponse('UID SEARCH', $criteria);
        foreach ($response as $ids) {
            if ($ids[0] === 'SEARCH') {
                array_shift($ids);

                return $ids;
            }
        }

        if (!is_array($response)) {
            throw new \Zend\Mail\Storage\Exception\RuntimeException('Cannot search messages.');
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function selectFolder($globalName)
    {
        if ((string)$this->currentFolder === (string)$globalName && $this->uidValidity !== null) {
            // The given folder already selected
            return;
        }

        $this->currentFolder = $globalName;
        $selectResponse = $this->protocol->select(
            mb_convert_encoding((string)$this->currentFolder, 'UTF7-IMAP', 'UTF-8')
        );
        if (!$selectResponse) {
            $this->currentFolder = '';
            throw new \Zend\Mail\Storage\Exception\RuntimeException('cannot change folder, maybe it does not exist');
        }

        $this->uidValidity = $selectResponse['uidvalidity'];
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->ignoreCloseCommand) {
            return;
        }

        parent::close();
    }

    /**
     * Creates Message object based on the given data
     *
     * @param int   $id
     * @param array $data
     *
     * @return Message
     */
    protected function createMessageObject($id, array $data)
    {
        $header = $data[self::BODY_HEADER];

        $flags = [];
        foreach ($data[self::FLAGS] as $flag) {
            $flags[] = isset(static::$knownFlags[$flag]) ? static::$knownFlags[$flag] : $flag;
        }

        /** @var \Zend\Mail\Storage\Message $message */
        $message = new $this->messageClass(
            [
                'handler' => $this,
                'id'      => $id,
                'headers' => $header,
                'flags'   => $flags
            ]
        );

        $headers = $message->getHeaders();
        $this->setExtHeaders($headers, $data);

        return $message;
    }

    public function getRawContent($id, $part = null)
    {
        if ($part !== null) {
            // TODO: implement
            throw new Exception\RuntimeException('not implemented');
        }

        return $this->protocol->fetch(self::BODY_PEEK_TEXT, $id);
    }

    /**
     * Get capabilities from IMAP server
     *
     * @return string[] list of capabilities
     */
    protected function getCapability()
    {
        return $this->protocol->capability();
    }

    /**
     * Sets additional message headers
     *
     * @param \Zend\Mail\Headers $headers
     * @param array $data
     */
    protected function setExtHeaders(&$headers, array $data)
    {
        $headers->addHeaderLine(self::UID, $data[self::UID]);
        $headers->addHeaderLine('InternalDate', $data[self::INTERNALDATE]);
    }
}
