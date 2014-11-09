<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;

class ActivityListTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $obj = new ActivityList();

        $this->setId($obj, 1);
        $this->assertEquals(1, $obj->getId());
    }

    public function testCreatedAtGetter()
    {
        $date = new \DateTime('now');

        $obj = new ActivityList();

        $this->setCreatedAt($obj, $date);
        $this->assertEquals($date, $obj->getCreatedAt());
    }

    /**
     * @dataProvider getSetDataProvider
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new ActivityList();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        return [
            ['verb', 'testVerb'],
            ['subject', 'testSubject'],
            ['data', ['test' => 'val']],
            ['relatedActivityClass', 'testRelatedActivityClass'],
            ['relatedActivityId', 123],
            ['updatedAt', new \DateTime('now')],
            ['organization', new Organization()]
        ];
    }

    public function testPrePersist()
    {
        $obj = new ActivityList();

        $this->assertNull($obj->getCreatedAt());
        $this->assertNull($obj->getUpdatedAt());

        $obj->prePersist();
        $this->assertInstanceOf('\DateTime', $obj->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $obj->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $obj = new ActivityList();

        $this->assertNull($obj->getUpdatedAt());

        $obj->preUpdate();
        $this->assertInstanceOf('\DateTime', $obj->getUpdatedAt());
    }

    /**
     * @param mixed $obj
     * @param mixed $val
     */
    protected function setId($obj, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
    }

    /**
     * @param mixed $obj
     * @param mixed $val
     */
    protected function setCreatedAt($obj, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop  = $class->getProperty('createdAt');
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
    }
}
