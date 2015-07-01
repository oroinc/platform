<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;

class EmailFolderTest extends \PHPUnit_Framework_TestCase
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
        $subFolder = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailFolder');
        $subFolder2 = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailFolder');

        $entity = new EmailFolder();
        $entity->addSubFolder($subFolder);
        $entity->addSubFolder($subFolder2);

        $subFolders = $entity->getSubFolders();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subFolders);
        $this->assertCount(2, $subFolders);
        $this->assertTrue($subFolder === $subFolders[0]);
        $this->assertTrue($subFolder2 === $subFolders[1]);
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new EmailFolder();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        $origin = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailOrigin');
        $parentFolder = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailFolder');
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
