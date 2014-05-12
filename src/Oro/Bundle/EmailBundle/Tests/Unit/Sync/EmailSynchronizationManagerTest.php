<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Sync\EmailSynchronizationManager;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;

class EmailSynchronizationManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testSyncOrigins()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $sync1 = $this->getMockForAbstractClass(
            'Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer',
            [],
            '',
            false,
            true,
            true,
            ['supports', 'syncOrigins']
        );
        $sync2 = $this->getMockForAbstractClass(
            'Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer',
            [],
            '',
            false,
            true,
            true,
            ['supports', 'syncOrigins']
        );

        $origin1 = new InternalEmailOrigin();
        $origin1->setName('origin1');
        ReflectionUtil::setId($origin1, 1);
        $origin2 = new InternalEmailOrigin();
        $origin2->setName('origin2');
        ReflectionUtil::setId($origin2, 2);
        $origin3 = new InternalEmailOrigin();
        $origin3->setName('origin3');
        ReflectionUtil::setId($origin3, 3);

        $sync1->expects($this->at(0))
            ->method('supports')
            ->with($origin1)
            ->will($this->returnValue(false));
        $sync1->expects($this->at(1))
            ->method('supports')
            ->with($origin2)
            ->will($this->returnValue(true));
        $sync1->expects($this->at(2))
            ->method('supports')
            ->with($origin3)
            ->will($this->returnValue(false));
        $sync1->expects($this->at(3))
            ->method('syncOrigins')
            ->with([2]);

        $sync2->expects($this->at(0))
            ->method('supports')
            ->with($origin1)
            ->will($this->returnValue(false));
        $sync2->expects($this->at(1))
            ->method('supports')
            ->with($origin2)
            ->will($this->returnValue(false));
        $sync2->expects($this->at(2))
            ->method('supports')
            ->with($origin3)
            ->will($this->returnValue(true));
        $sync2->expects($this->at(3))
            ->method('syncOrigins')
            ->with([3]);

        $container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['sync1', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $sync1],
                        ['sync2', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $sync2],
                    ]
                )
            );

        $manager = new EmailSynchronizationManager($container);
        $manager->addSynchronizer('sync1');
        $manager->addSynchronizer('sync2');

        $manager->syncOrigins([$origin1, $origin2, $origin3]);
    }
}
