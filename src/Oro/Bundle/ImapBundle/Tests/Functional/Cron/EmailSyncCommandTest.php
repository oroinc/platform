<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\Cron;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Tests\Functional\EmailFeatureTrait;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Connector\ImapServices;
use Oro\Bundle\ImapBundle\Connector\ImapServicesFactory;
use Oro\Bundle\ImapBundle\Connector\Search\SearchStringManagerInterface;
use Oro\Bundle\ImapBundle\Mail\Protocol\Imap as ProtocolImap;
use Oro\Bundle\ImapBundle\Mail\Storage\Imap;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

/**
 * @dbIsolationPerTest
 */
class EmailSyncCommandTest extends WebTestCase
{
    use EmailFeatureTrait;

    /** @var ImapConnectorFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $imapConnectorFactory;

    /** @var ImapServicesFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $serviceFactory;

    /** @var ImapServices|\PHPUnit\Framework\MockObject\MockObject */
    protected $imapServices;

    /** @var Imap|\PHPUnit\Framework\MockObject\MockObject */
    protected $imap;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $protocol;

    /** @var SearchStringManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $searchStringManager;

    protected function setUp()
    {
        $this->initClient();

        $this->imapConnectorFactory = $this->getMockBuilder('Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->serviceFactory = $this->getMockBuilder('Oro\Bundle\ImapBundle\Connector\ImapServicesFactory')
            ->setMethods(['createImapServices', 'getDefaultImapStorage'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->imapServices = $this->getMockBuilder('Oro\Bundle\ImapBundle\Connector\ImapServices')
            ->disableOriginalConstructor()
            ->setMethods(['getStorage', 'getSearchStringManager'])
            ->getMock();

        $this->imap = new TestImap([]);

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

        $this->mockProtocol();

        $imapConfig = new ImapConfig();
        $imapConnector = new ImapConnector($imapConfig, $this->serviceFactory);
        $this->imapConnectorFactory->expects($this->any())
            ->method('createImapConnector')
            ->will($this->returnValue($imapConnector));

        $this->getContainer()->set('oro_imap.connector.factory', $this->imapConnectorFactory);

        $this->enableEmailFeature();
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

    public function testCommandOutputWithEmailFeatureDisabled()
    {
        $this->disableEmailFeature();
        $result = $this->runCommand('oro:cron:imap-sync', []);

        $this->assertContains('The email feature is disabled. The command will not run.', $result);
    }

    /**
     * @dataProvider commandImapSyncProvider
     * @param array $params
     * @param array $data
     * @param int $assertCount
     * @param array $expectedList
     * @param array $expectedEmailData
     * @param string $expectedEmailDataDbType
     */
    public function testImapSync(
        array $params,
        $data,
        $assertCount,
        $expectedList,
        $expectedEmailData,
        $expectedEmailDataDbType
    ) {
        $this->loadFixtures(['Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadOriginData']);

        $this->protocol->expects($this->any())
            ->method('fetch')
            ->willReturn([1 => $data]);

        $this->protocol->expects($this->any())
            ->method('search')
            ->willReturn($assertCount ? [1] : []);

        if (isset($params['--id'])) {
            $params['--id'] = (string)$this->getReference($params['--id'])->getId();
        }

        $result = $this->runCommand('oro:cron:imap-sync', $params);
        foreach ($expectedList as $expected) {
            $this->assertContains($expected, $result);
        }

        $listRepo = $this->getContainer()->get('doctrine')->getRepository('OroEmailBundle:Email');
        $list = $listRepo->findAll();

        $this->assertEquals($assertCount, count($list));

        if ($expectedEmailData && $this->isDbTypeIsValid($expectedEmailDataDbType)) {
            /** @var Email $email */
            $email = $list[0];
            $propertyAccessor = new PropertyAccessor();
            foreach ($expectedEmailData as $propertyPath => $expectedValue) {
                if (strpos($propertyPath, '@') !== false) {
                    list($propertyPath, $extension) = explode('@', $propertyPath);
                    if ($extension && !extension_loaded($extension)) {
                        continue;
                    }
                }

                $pathValue = $propertyAccessor->getValue($email, $propertyPath);
                if (null === $expectedValue && is_object($pathValue) && $pathValue instanceof Collection) {
                    $this->assertEquals(0, $pathValue->count());
                } else {
                    $this->assertEquals($expectedValue, $pathValue);
                }
            }
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
            $expectedEmailDataDbType = isset($data['expectedEmailDataDbType']) ? $data['expectedEmailDataDbType'] : '';
            $results[$data['id']] = [
                'params'            => $data['params'],
                'data'              => $data['data'],
                'assertCount'       => $data['total'],
                'expectedContent'   => $data['log_info'],
                'expectedEmailData' => $data['expectedEmailData'],
                'expectedEmailDataDbType' => $expectedEmailDataDbType
            ];
        }

        return $results;
    }

    /**
     * @param string $expectedEmailDataDbType
     *
     * @return bool
     */
    private function isDbTypeIsValid($expectedEmailDataDbType)
    {
        if ($expectedEmailDataDbType === '') {
            return true;
        }

        $platform  = $this->getContainer()->get('doctrine')->getConnection()->getDatabasePlatform()->getName();

        return $expectedEmailDataDbType === $platform;
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

    protected function mockProtocol()
    {
        $this->protocol = $this->createMock(ProtocolImap::class);
        $this->imap->setProtocol($this->protocol);
        $this->protocol->expects($this->any())
            ->method('select')
            ->willReturn(['uidvalidity' => 100]);
        $this->protocol->expects($this->any())
            ->method('capability')
            ->willReturn(['CAPABILITY', 'IMAP4']);
        $this->protocol->expects($this->any())
            ->method('requestAndResponse')
            ->willReturn([['SEARCH', '123']]);
    }
}
