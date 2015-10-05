<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Sync;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;
use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizationProcessor;

class ImapEmailSynchronizationProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImapEmailSynchronizationProcessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $addrManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $addrChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $imapManager;

    protected function setUp()
    {
        $this->logger        = $this->getMock('Psr\Log\LoggerInterface');
        $this->em            = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityBuilder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->addrManager   = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->addrChecker   = $this->getMockBuilder('Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->imapManager   = $this->getMockBuilder('Oro\Bundle\ImapBundle\Manager\ImapEmailManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetFolders()
    {
        $this->logger->expects($this->any())
            ->method('notice');

        $inboxFolder         = $this->createRemoteFolder('Inbox', '[Gmail]\Inbox', ['\Inbox']);
        $subFolder           = $this->createRemoteFolder('Inbox', '[Gmail]\Test');
        $sentFolder          = $this->createRemoteFolder('Sent', '[Gmail]\Sent', ['\Sent']);
        $spamFolder          = $this->createRemoteFolder('Spam', '[Gmail]\Spam', ['\Spam']);
        $trashFolder         = $this->createRemoteFolder('Spam', '[Gmail]\Trash', ['\Trash']);
        $nonSelectableFolder = $this->createRemoteFolder('All', 'All', [], false);

        $this->imapManager->expects($this->once())
            ->method('getFolders')
            ->with(null, true)
            ->will(
                $this->returnValue(
                    [
                        $inboxFolder,
                        $subFolder,
                        $sentFolder,
                        $spamFolder,
                        $trashFolder,
                        $nonSelectableFolder
                    ]
                )
            );

        $processor = new ImapEmailSynchronizationProcessor(
            $this->em,
            $this->entityBuilder,
            $this->addrChecker,
            $this->imapManager
        );
        $processor->setLogger($this->logger);

        $srcFolders = ReflectionUtil::callProtectedMethod($processor, 'getFolders', []);
        $this->assertEquals(
            [$inboxFolder, $subFolder, $sentFolder],
            $srcFolders
        );
    }

    /**
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ImapEmailSynchronizationProcessor
     */
    protected function getProcessorMock(array $methods = [])
    {
        $processor = $this->getMock(
            'Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizationProcessor',
            $methods,
            [
                $this->em,
                $this->entityBuilder,
                $this->addrChecker,
                $this->imapManager
            ]
        );
        $processor->setLogger($this->logger);

        return $processor;
    }

    /**
     * @param string $localName
     * @param string $globalName
     * @param array  $flags
     * @param bool   $selectable
     *
     * @return Folder
     */
    protected function createRemoteFolder($localName, $globalName, array $flags = [], $selectable = true)
    {
        $folder = new Folder($localName, $globalName, $selectable);
        $folder->setFlags($flags);

        return $folder;
    }

    /**
     * @param string   $folderName
     * @param string   $folderFullName
     * @param int      $uidValidity
     * @param int|null $id
     *
     * @return ImapEmailFolder
     */
    protected function createImapFolder($folderName, $folderFullName, $uidValidity, $id = null)
    {
        $folder = new EmailFolder();
        $folder
            ->setName($folderName)
            ->setFullName($folderFullName);

        $imapFolder = new ImapEmailFolder();
        $imapFolder
            ->setFolder($folder)
            ->setUidValidity($uidValidity);
        if ($id !== null) {
            ReflectionUtil::setId($imapFolder, $id);
        }

        return $imapFolder;
    }
}
