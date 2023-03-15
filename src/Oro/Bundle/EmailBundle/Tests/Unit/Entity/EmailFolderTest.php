<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Component\Testing\ReflectionUtil;

class EmailFolderTest extends \PHPUnit\Framework\TestCase
{
    public function testIdGetter()
    {
        $entity = new EmailFolder();
        ReflectionUtil::setId($entity, 1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testOutdatedAt()
    {
        $entity = new EmailFolder();
        $this->assertFalse($entity->isOutdated());

        $date = new \DateTime();
        $entity->setOutdatedAt($date);
        $this->assertEquals($date, $entity->getOutdatedAt());
        $this->assertTrue($entity->isOutdated());

        $entity->setOutdatedAt(null);
        $this->assertFalse($entity->isOutdated());
    }

    public function testFolderGetterAndSetter()
    {
        $subFolder = $this->createMock(EmailFolder::class);
        $subFolder2 = $this->createMock(EmailFolder::class);

        $entity = new EmailFolder();
        $entity->addSubFolder($subFolder);
        $entity->addSubFolder($subFolder2);

        $subFolders = $entity->getSubFolders();

        $this->assertInstanceOf(ArrayCollection::class, $subFolders);
        $this->assertCount(2, $subFolders);
        $this->assertSame($subFolder, $subFolders[0]);
        $this->assertSame($subFolder2, $subFolders[1]);
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $obj = new EmailFolder();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        $origin = $this->createMock(EmailOrigin::class);
        $parentFolder = $this->createMock(EmailFolder::class);
        $synchronizedAt = new \DateTime();

        return [
            ['type', 'test'],
            ['fullName', 'test'],
            ['name', 'test'],
            ['origin', $origin],
            ['syncEnabled', true],
            ['syncEnabled', false],
            ['parentFolder', $parentFolder],
            ['synchronizedAt', $synchronizedAt],
        ];
    }
}
