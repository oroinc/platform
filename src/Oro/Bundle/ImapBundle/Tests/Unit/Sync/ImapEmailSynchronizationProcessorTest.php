<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Sync;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;
use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizationProcessor;

class ImapEmailSynchronizationProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImapEmailSynchronizationProcessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $loggerMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityBuilderMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $addrManagerMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $addrCheckerMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $imapManagerMock;

    protected function setUp()
    {
        $this->loggerMock        = $this->getMock('Psr\Log\LoggerInterface');
        $this->emMock            = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityBuilderMock = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->addrManagerMock   = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->addrCheckerMock   = $this->getMockBuilder('Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker')
            ->disableOriginalConstructor()
            ->getMock();
        $this->imapManagerMock   = $this->getMockBuilder('Oro\Bundle\ImapBundle\Manager\ImapEmailManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSyncFolders()
    {
        $origin     = new ImapEmailOrigin();
        $existingImapFolders = $this->getExistingImapFolders();

        $this->loggerMock->expects($this->any())
            ->method('notice');

        // load existing imap folders
        $repoMock = $this->getMockBuilder('Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailFolderRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repoMock->expects($this->once())
            ->method('getFoldersByOrigin')
            ->with($origin)
            ->will($this->returnValue($existingImapFolders));

        $this->emMock->expects($this->once())
            ->method('getRepository')
            ->with('OroImapBundle:ImapEmailFolder')
            ->will($this->returnValue($repoMock));

        $this->emMock->expects($this->any())
            ->method('persist');
        $this->emMock->expects($this->once())
            ->method('flush');


        $processor   = $this->getProcessorMock(['loadSourceFolders']);

        // load remote folders
        $srcFolders = $this->getRemoteFolders();
        $processor->expects($this->once())
            ->method('loadSourceFolders')
            ->will($this->returnValue($srcFolders));

        $imapFolders = $this->callProtectedMethod($processor, 'syncFolders', [$origin]);

        $this->assertCount(4, $imapFolders);
        $this->assertInstanceOf('Oro\Bundle\ImapBundle\Entity\ImapEmailFolder', $imapFolders[0]);
        $this->assertInstanceOf('Oro\Bundle\ImapBundle\Entity\ImapEmailFolder', $imapFolders[1]);
    }

    public function testLoadSourceFolders()
    {
        $this->loggerMock->expects($this->any())
            ->method('notice');

        $folder1 = new Folder('Inbox', '[Gmail]\Inbox');
        $folder1->setFlags(['\Inbox']);

        $folder2 = new Folder('Sent', '[Gmail]\Sent');
        $folder2->setFlags(['\Sent']);

        $folder3 = new Folder('Spam', '[Gmail]\Spam');
        $folder3->setFlags(['\Spam']);

        $folder4 = new Folder('All', 'All', false);

        $srcFolders  = [$folder1, $folder2, $folder3, $folder4];

        $this->imapManagerMock->expects($this->once())
            ->method('getFolders')
            ->with(null, true)
            ->will($this->returnValue($srcFolders));

        $this->imapManagerMock->expects($this->exactly(2))
            ->method('selectFolder')
            ->will($this->returnValue($srcFolders));

        $uidValidity = 0;
        $this->imapManagerMock->expects($this->at(2))
            ->method('getUidValidity')
            ->will($this->returnValue($uidValidity++));

        $this->imapManagerMock->expects($this->at(4))
            ->method('getUidValidity')
            ->will($this->returnValue($uidValidity++));

        $processor = $this->getProcessorMock();

        $srcFolders = $this->callProtectedMethod($processor, 'loadSourceFolders');
        $this->assertCount(2, $srcFolders);
    }

    /**
     * @return array
     */
    protected function getExistingImapFolders()
    {
        $folder1     = new EmailFolder();
        $folder1->setFullName('existing');

        // existing with uidvalidity equal
        $imapFolder1 = new ImapEmailFolder();
        $imapFolder1
            ->setFolder($folder1)
            ->setUidValidity(4); // corresponding to $folder3 in getRemoteFolders

        $folder2     = new EmailFolder();
        $folder2->setFullName('Test');

        // existing with new uid validity
        $imapFolder2 = new ImapEmailFolder();
        $imapFolder2->setFolder($folder2)
            ->setUidValidity(15);
        $this->setProtectedProperty($imapFolder2, 'id', 1);

        return [
            $imapFolder1,
            $imapFolder2,
        ];
    }

    /**
     * @return array
     */
    protected function getRemoteFolders()
    {
        $folder1 = new Folder('Inbox', '[Gmail]\Inbox');
        $folder1->setFlags(['\Inbox']);

        $folder2 = new Folder('Sent', '[Gmail]\Sent');
        $folder2->setFlags(['\Sent']);

        $folder3 = new Folder('existing', 'existing');

        return [
            // uid validity => Folder
            1  => $folder1,
            3  => $folder2,
            4  => $folder3,
        ];
    }

    /**
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ImapEmailSynchronizationProcessor
     */
    protected function getProcessorMock(array $methods = [])
    {
        return $this->getMock(
            'Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizationProcessor',
            $methods,
            [
                $this->loggerMock,
                $this->emMock,
                $this->entityBuilderMock,
                $this->addrManagerMock,
                $this->addrCheckerMock,
                $this->imapManagerMock
            ]
        );
    }

    /**
     * @param object $obj
     * @param string $methodName
     * @param array  $args
     *
     * @return mixed
     */
    protected function callProtectedMethod($obj, $methodName, $args = [])
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    /**
     * @param object $obj
     * @param string $property
     * @param mixed  $value
     */
    protected function setProtectedProperty($obj, $property, $value)
    {
        $class = new \ReflectionClass($obj);
        $property = $class->getProperty($property);
        $property->setAccessible(true);

        $property->setValue($obj, $value);
    }
}
