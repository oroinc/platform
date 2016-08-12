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
 * @dbIsolationPerTest
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

    /**
     * @dataProvider commandImapSyncProvider
     * @param string $commandName
     * @param array $params
     * @param array $data
     * @param string $assertMethod
     * @param int $assertCount
     * @param array $expectedList
     */
    public function testImapSync(
        $commandName,
        array $params,
        $data,
        $assertMethod,
        $assertCount,
        $expectedList
    ) {
        $this->loadFixtures(['Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadOriginData']);

        $this->mockProtocol($data, $assertCount);

        if (isset($params['--id'])) {
            $params['--id'] = (string)$this->getReference($params['--id'])->getId();
        }
        $result = $this->runCommand($commandName, $params);
        foreach ($expectedList as $expected) {
            $this->assertContains($expected, $result);
        }
        if ($assertMethod) {
            $listRepo = $this->getContainer()->get('doctrine')->getRepository('OroEmailBundle:Email');
            $list = $listRepo->findAll();
            $this->$assertMethod($assertCount, count($list));
        }
    }

    /**
     * @return array
     */
    public function commandImapSyncProvider()
    {
        $results = [];
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'ImapResponses';
        $apiData = $this->getRequestsData($path);

        foreach ($apiData as $data) {
            $results[$data['id']] = [
                'commandName'     => 'oro:cron:imap-sync',
                'params'          => $data['params'],
                'data'            => $data['data'],
                'assertMethod'    => 'assertEquals',
                'assertCount'     => $data['total'],
                'expectedContent' => $data['log_info']
            ];
        }

        return $results;
    }

    /**
     * @param string $folder
     *
     * @return array
     */
    public static function getRequestsData($folder)
    {
        $parameters = [];
        $testFiles = new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS);
        foreach ($testFiles as $fileName => $object) {
            $parameters[$object->getFilename()] = Yaml::parse(file_get_contents($fileName)) ?: [];
        }
        ksort($parameters);

        return $parameters;
    }

    /**
     * @param mixed $data
     * @param int $assertCount
     */
    protected function mockProtocol($data, $assertCount)
    {
        $uid = isset($data[Imap::UID]) ? $data[Imap::UID] : 0;
        $internaldate = isset($data[Imap::INTERNALDATE]) ? $data[Imap::INTERNALDATE] : 0;

        $message = new Message($data);
        $headers = $message->getHeaders();
        $headers->addHeaderLine(Imap::UID, $uid);
        $headers->addHeaderLine('InternalDate', $internaldate);

        $this->imap->expects($this->any())
            ->method('getMessage')
            ->willReturn($message);
        $this->imap->expects($this->any())
            ->method('getMessages')
            ->willReturn([1 => $message]);
        $this->imap->expects($this->any())
            ->method('search')
            ->willReturn([1]);
        $this->imap->expects($this->any())
            ->method('uidSearch')
            ->willReturn([$uid]);
        $this->imap->expects($this->any())
            ->method('count')
            ->willReturn($assertCount);
    }
}
