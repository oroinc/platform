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
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadOriginData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

/**
 * @dbIsolationPerTest
 */
class EmailSyncCommandTest extends WebTestCase
{
    use EmailFeatureTrait;

    /** @var Imap|\PHPUnit\Framework\MockObject\MockObject */
    private $imap;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $protocol;

    protected function setUp(): void
    {
        $this->initClient();

        $serviceFactory = $this->getMockBuilder(ImapServicesFactory::class)
            ->onlyMethods(['createImapServices', 'getDefaultImapStorage'])
            ->disableOriginalConstructor()
            ->getMock();
        $imapServices = $this->getMockBuilder(ImapServices::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStorage', 'getSearchStringManager'])
            ->getMock();

        $this->imap = new TestImap([]);

        $imapServices->expects($this->any())
            ->method('getStorage')
            ->willReturn($this->imap);
        $imapServices->expects($this->any())
            ->method('getSearchStringManager')
            ->willReturn($this->createMock(SearchStringManagerInterface::class));

        $serviceFactory->expects($this->any())
            ->method('createImapServices')
            ->willReturn($imapServices);
        $serviceFactory->expects($this->any())
            ->method('getDefaultImapStorage')
            ->willReturn($imapServices);

        $this->mockProtocol();

        $imapConfig = new ImapConfig();
        $imapConnector = new ImapConnector($imapConfig, $serviceFactory);
        $imapConnectorFactory = $this->createMock(ImapConnectorFactory::class);
        $imapConnectorFactory->expects($this->any())
            ->method('createImapConnector')
            ->willReturn($imapConnector);

        $this->getContainer()->set('oro_imap.connector.factory', $imapConnectorFactory);

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
            self::assertStringContainsString($expected, $result);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @dataProvider commandImapSyncProvider
     */
    public function testImapSync(
        string $id,
        array $params,
        array $data,
        int $assertCount,
        array $expectedList,
        ?array $expectedEmailData,
        string $expectedEmailDataDbType
    ) {
        if (!$this->isDbTypeIsValid($expectedEmailDataDbType)) {
            $this->markTestSkipped(sprintf('%s might be checked only with %s', $id, $expectedEmailDataDbType));
        }
        $this->loadFixtures([LoadOriginData::class]);

        $this->protocol->expects($this->any())
            ->method('fetch')
            ->willReturn([1 => $data]);

        $this->protocol->expects($this->any())
            ->method('search')
            ->willReturn($assertCount ? [1] : []);

        $this->protocol->expects($this->any())
           ->method('listMailbox')
           ->willReturn(['INBOX' => ['delim' => '/', 'flags' => []]]);

        if (isset($params['--id'])) {
            $params['--id'] = (string)$this->getReference($params['--id'])->getId();
        }

        $result = $this->runCommand('oro:cron:imap-sync', $params);
        foreach ($expectedList as $expected) {
            self::assertStringContainsString($expected, $result);
        }

        $listRepo = $this->getContainer()->get('doctrine')->getRepository(Email::class);
        $list = $listRepo->findAll();

        $this->assertCount($assertCount, $list);

        if ($expectedEmailData) {
            /** @var Email $email */
            $email = $list[0];
            $propertyAccessor = new PropertyAccessor();
            foreach ($expectedEmailData as $propertyPath => $expectedValue) {
                if (str_contains($propertyPath, '@')) {
                    [$propertyPath, $extension] = explode('@', $propertyPath);
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

    public function commandImapSyncProvider(): array
    {
        $results = [];
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'ImapResponses';
        $apiData = $this->getRequestsData($path);

        foreach ($apiData as $data) {
            $expectedEmailDataDbType = $data['expectedEmailDataDbType'] ?? '';
            $results[$data['id']] = [
                'id' => $data['id'],
                'params' => $data['params'],
                'data' => $data['data'],
                'assertCount' => $data['total'],
                'expectedContent' => $data['log_info'],
                'expectedEmailData' => $data['expectedEmailData'],
                'expectedEmailDataDbType' => $expectedEmailDataDbType
            ];
        }

        return $results;
    }

    private function isDbTypeIsValid(string $expectedEmailDataDbType): bool
    {
        if ('' === $expectedEmailDataDbType) {
            return true;
        }

        $platform = $this->getContainer()->get('doctrine')->getConnection()->getDatabasePlatform()->getName();

        return $expectedEmailDataDbType === $platform;
    }

    private function getRequestsData(string $folder): array
    {
        $parameters = [];
        $testFiles = new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS);
        foreach ($testFiles as $fileName => $object) {
            $parameters[$object->getFilename()] = Yaml::parse(file_get_contents($fileName)) ?: [];
        }
        ksort($parameters);

        return $parameters;
    }

    private function mockProtocol(): void
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
