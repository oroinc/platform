<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\EmailBundle\Sync\EmailSynchronizationManager;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailSynchronizationManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testSyncOrigins()
    {
        $container = $this->createMock(ContainerInterface::class);

        $sync1 = $this->createMock(AbstractEmailSynchronizer::class);
        $sync2 = $this->createMock(AbstractEmailSynchronizer::class);

        $origin1 = new InternalEmailOrigin();
        $origin1->setName('origin1');
        ReflectionUtil::setId($origin1, 1);
        $origin2 = new InternalEmailOrigin();
        $origin2->setName('origin2');
        ReflectionUtil::setId($origin2, 2);
        $origin3 = new InternalEmailOrigin();
        $origin3->setName('origin3');
        ReflectionUtil::setId($origin3, 3);

        $sync1->expects($this->exactly(3))
            ->method('supports')
            ->willReturnMap([
                [$origin1, false],
                [$origin2, true],
                [$origin3, false]
            ]);
        $sync1->expects($this->once())
            ->method('syncOrigins')
            ->with([2]);

        $sync2->expects($this->exactly(3))
            ->method('supports')
            ->willReturnMap([
                [$origin1, false],
                [$origin2, false],
                [$origin3, true]
            ]);
        $sync2->expects($this->once())
            ->method('syncOrigins')
            ->with([3]);

        $container->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['sync1', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $sync1],
                ['sync2', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $sync2],
            ]);

        $manager = new EmailSynchronizationManager($container);
        $manager->addSynchronizer('sync1');
        $manager->addSynchronizer('sync2');

        $manager->syncOrigins([$origin1, $origin2, $origin3]);
    }
}
