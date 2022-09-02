<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Component\Testing\ReflectionUtil;

class EmailOriginTest extends \PHPUnit\Framework\TestCase
{
    public function testIdGetter()
    {
        $entity = new TestEmailOrigin();
        ReflectionUtil::setId($entity, 1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testFolderGetterAndSetter()
    {
        $folder = $this->createMock(EmailFolder::class);

        $entity = new TestEmailOrigin();
        $entity->addFolder($folder);

        $folders = $entity->getFolders();

        $this->assertInstanceOf(ArrayCollection::class, $folders);
        $this->assertCount(1, $folders);
        $this->assertSame($folder, $folders[0]);
    }

    public function testIsActive()
    {
        $entity = new TestEmailOrigin();

        // check  that true by default
        $this->assertTrue($entity->isActive());

        // check setter
        $entity->setActive(false);
        $this->assertFalse($entity->isActive());
    }

    public function testIsSyncEnabled(): void
    {
        $entity = new TestEmailOrigin();
        self::assertTrue($entity->isSyncEnabled());
        $entity->setIsSyncEnabled(false);
        self::assertFalse($entity->isSyncEnabled());
        $entity->setIsSyncEnabled(true);
        self::assertTrue($entity->isSyncEnabled());
    }
}
