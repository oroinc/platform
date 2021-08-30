<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Cache;

use Oro\Bundle\EmailBundle\Cache\EntityCacheWarmer;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class EntityCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    private function getRenderParameters(string $ext): array
    {
        return [
            'EmailAddress' . $ext . '.twig',
            [
                'namespace' => 'Test\SomeNamespace',
                'className' => 'TestEmailAddressProxy',
                'owners'    => [
                    [
                        'targetEntity' => 'Oro\TestUser',
                        'columnName'   => 'owner_testuser_id',
                        'fieldName'    => 'owner1'
                    ],
                    [
                        'targetEntity' => 'Oro\TestContact',
                        'columnName'   => 'owner_testcontact_id',
                        'fieldName'    => 'owner2'
                    ],
                    [
                        'targetEntity' => 'Acme\TestUser',
                        'columnName'   => 'owner_acme_testuser_id',
                        'fieldName'    => 'owner3'
                    ],
                ]
            ]
        ];
    }

    private function getWriteCacheFileParameters(string $ext): array
    {
        return [
            'SomeDir' . DIRECTORY_SEPARATOR . 'TestEmailAddressProxy' . $ext,
            'test' . $ext
        ];
    }

    public function testWarmUpAndIsOptional()
    {
        $oroProvider = $this->createMock(EmailOwnerProviderInterface::class);
        $oroProvider->expects($this->any())
            ->method('getEmailOwnerClass')
            ->willReturn('Oro\TestUser');

        $oroCrmProvider = $this->createMock(EmailOwnerProviderInterface::class);
        $oroCrmProvider->expects($this->any())
            ->method('getEmailOwnerClass')
            ->willReturn('Oro\TestContact');

        $acmeProvider = $this->createMock(EmailOwnerProviderInterface::class);
        $acmeProvider->expects($this->any())
            ->method('getEmailOwnerClass')
            ->willReturn('Acme\TestUser');

        $storage = new EmailOwnerProviderStorage();
        $storage->addProvider($oroProvider);
        $storage->addProvider($oroCrmProvider);
        $storage->addProvider($acmeProvider);

        $kernel = $this->createMock(KernelInterface::class);
        $warmer = $this->getMockBuilder(EntityCacheWarmer::class)
            ->setConstructorArgs([$storage, 'SomeDir', 'Test\SomeNamespace', 'Test%sProxy', $kernel])
            ->onlyMethods(['createFilesystem', 'createTwigEnvironment', 'writeCacheFile'])
            ->getMock();

        $fs = $this->createMock(Filesystem::class);
        $twig = $this->createMock(Environment::class);

        $warmer->expects($this->once())
            ->method('createFilesystem')
            ->willReturn($fs);
        $warmer->expects($this->once())
            ->method('createTwigEnvironment')
            ->willReturn($twig);

        $fs->expects($this->once())
            ->method('exists')
            ->with('SomeDir');
        $fs->expects($this->once())
            ->method('mkdir')
            ->with('SomeDir', 0777);

        $twig->expects($this->exactly(2))
            ->method('render')
            ->withConsecutive(
                $this->getRenderParameters('.php'),
                $this->getRenderParameters('.orm.yml')
            )
            ->willReturnOnConsecutiveCalls(
                'test.php',
                'test.orm.yml'
            );
        $warmer->expects($this->exactly(2))
            ->method('writeCacheFile')
            ->withConsecutive(
                $this->getWriteCacheFileParameters('.php'),
                $this->getWriteCacheFileParameters('.orm.yml')
            );

        $warmer->warmUp('');
        $this->assertFalse($warmer->isOptional());
    }
}
