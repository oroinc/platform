<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector;

use Oro\Bundle\ImapBundle\Connector\Exception\InvalidConfigurationException;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapServices;
use Oro\Bundle\ImapBundle\Connector\ImapServicesFactory;
use Oro\Bundle\ImapBundle\Tests\Unit\Connector\TestFixtures\Imap1;
use Oro\Bundle\ImapBundle\Tests\Unit\Connector\TestFixtures\Imap2;
use Oro\Bundle\ImapBundle\Tests\Unit\Connector\TestFixtures\SearchStringManager1;
use Oro\Bundle\ImapBundle\Tests\Unit\Connector\TestFixtures\SearchStringManager2;
use PHPUnit\Framework\TestCase;

class ImapServicesFactoryTest extends TestCase
{
    public function testMissingDefaultServices(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new ImapServicesFactory(['TEST' => ['StorageClass', 'SearchStringManagerClass']]);
    }

    public function testCreateImapServicesForDefaultServices(): void
    {
        $config = [
            '' => [
                Imap1::class,
                SearchStringManager1::class
            ],
            'FEATURE2' => [
                Imap2::class,
                SearchStringManager2::class
            ]
        ];

        $factory = new ImapServicesFactory($config);
        $services = $factory->createImapServices(new ImapConfig());

        $expected = new ImapServices(new TestFixtures\Imap1([]), new TestFixtures\SearchStringManager1());
        $this->assertEquals($expected, $services);
    }

    public function testCreateImapServicesForOtherServices(): void
    {
        $config = [
            '' => [
                Imap2::class,
                SearchStringManager2::class
            ],
            'FEATURE2' => [
                Imap1::class,
                SearchStringManager1::class
            ]
        ];

        $factory = new ImapServicesFactory($config);

        $services = $factory->createImapServices(new ImapConfig());

        $expected = new ImapServices(new TestFixtures\Imap1([]), new TestFixtures\SearchStringManager1());

        $this->assertEquals($expected, $services);
    }
}
