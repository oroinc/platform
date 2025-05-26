<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class EmailOriginTest extends TestCase
{
    public function testIdGetter(): void
    {
        $entity = new TestEmailOrigin();
        ReflectionUtil::setId($entity, 1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testFolderGetterAndSetter(): void
    {
        $folder1 = new EmailFolder();
        $folder1->setType('inbox');
        $folder1->setName('Test1');
        $folder1->setFullName('Inbox/Test1');

        $folder2 = new EmailFolder();
        $folder2->setType('inbox');
        $folder2->setName('Test2');
        $folder2->setFullName('Inbox/Test2');

        $entity = new TestEmailOrigin();
        $entity->addFolder($folder1);
        $entity->addFolder($folder2);

        $folders = $entity->getFolders();

        $this->assertInstanceOf(ArrayCollection::class, $folders);
        $this->assertCount(2, $folders);
        $this->assertSame($folder1, $folders[0]);
        $this->assertSame($folder2, $folders[1]);

        $this->assertSame($folder1, $entity->getFolder('inbox'));
        $this->assertSame($folder1, $entity->getFolder('inbox', 'Inbox/Test1'));
        $this->assertSame($folder2, $entity->getFolder('inbox', 'Inbox/Test2'));
        $this->assertNull($entity->getFolder('inbox', 'Inbox/Test3'));
        $this->assertNull($entity->getFolder('sent'));
    }

    public function testIsActive(): void
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
