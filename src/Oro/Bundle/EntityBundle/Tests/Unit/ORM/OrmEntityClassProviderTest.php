<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\ManagerBagInterface;
use Oro\Bundle\EntityBundle\ORM\OrmEntityClassProvider;
use Oro\Bundle\EntityBundle\ORM\ShortClassMetadata;

class OrmEntityClassProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetClassNames()
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $em = $this->createMock(ObjectManager::class);
        $managerBag = $this->createMock(ManagerBagInterface::class);
        $managerBag->expects($this->any())
            ->method('getManagers')
            ->willReturn([$em]);

        $doctrineHelper->expects($this->once())
            ->method('getAllShortMetadata')
            ->with($this->identicalTo($em))
            ->willReturn(
                [
                    new ShortClassMetadata('Test\Entity1'),
                    new ShortClassMetadata('Test\Entity2', true)
                ]
            );

        $entityClassProvider = new OrmEntityClassProvider($doctrineHelper, $managerBag);

        $this->assertEquals(
            ['Test\Entity1'],
            $entityClassProvider->getClassNames()
        );
    }
}
