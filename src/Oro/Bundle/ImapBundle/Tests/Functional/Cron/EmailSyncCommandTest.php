<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\Cron;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Connector\ImapServices;
use Oro\Bundle\ImapBundle\Connector\ImapServicesFactory;
use Oro\Bundle\ImapBundle\Connector\Search\SearchStringManagerInterface;
use Oro\Bundle\ImapBundle\Mail\Storage\Message;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ImapBundle\Mail\Storage\Imap;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailSyncCommandTest extends WebTestCase
{
    /** @var ImapConnectorFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $imapConnectorFactory;

    /** @var ImapServicesFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $serviceFactory;

    /** @var ImapServices|\PHPUnit_Framework_MockObject_MockObject */
    protected $imapServices;

    /** @var Imap|\PHPUnit_Framework_MockObject_MockObject */
    protected $imap;

    /** @var SearchStringManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $searchStringManager;

    protected function setUp()
    {
        $this->initClient();

        $this->imapConnectorFactory = $this->getMockBuilder('Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->serviceFactory = $this->getMockBuilder('Oro\Bundle\ImapBundle\Connector\ImapServicesFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->imapServices = $this->getMockBuilder('Oro\Bundle\ImapBundle\Connector\ImapServices')
            ->disableOriginalConstructor()
            ->setMethods(['getStorage', 'getSearchStringManager'])
            ->getMock();
        $this->imap = $this->getMockBuilder('Oro\Bundle\ImapBundle\Mail\Storage\Imap')
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchStringManager = $this
            ->getMockBuilder('Oro\Bundle\ImapBundle\Connector\Search\SearchStringManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->imapServices->expects($this->any())
            ->method('getStorage')
            ->will($this->returnValue($this->imap));
        $this->imapServices->expects($this->any())
            ->method('getSearchStringManager')
            ->will($this->returnValue($this->searchStringManager));

        $this->serviceFactory->expects($this->any())
            ->method('createImapServices')
            ->will($this->returnValue($this->imapServices));
        $this->serviceFactory->expects($this->any())
            ->method('getDefaultImapStorage')
            ->will($this->returnValue($this->imapServices));

        $imapConfig = new ImapConfig();
        $imapConnector = new ImapConnector($imapConfig, $this->serviceFactory);
        $this->imapConnectorFactory->expects($this->any())
            ->method('createImapConnector')
            ->will($this->returnValue($imapConnector));

        $this->getContainer()->set('oro_imap.connector.factory', $this->imapConnectorFactory);
    }

    public function testImapSyncNoOrigins()
    {
        $expectedList = [
            'Resetting hanged email origins ...',
            'Updated 0 row(s).',
            'Finding an email origin ...',
            'An email origin was not found.',
            'Exit because nothing to synchronise.',
        ];
        $result = $this->runCommand('oro:cron:imap-sync', []);
        foreach ($expectedList as $expected) {
            $this->assertContains($expected, $result);
        }
    }
}
