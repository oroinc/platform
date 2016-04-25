<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Placeholder;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NoteBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\NoteBundle\Tests\Unit\Fixtures\TestEntity;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_REFERENCE = 'Oro\Bundle\NoteBundle\Tests\Unit\Fixtures\TestEntity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $noteAssociationHelper;

    /** @var PlaceholderFilter */
    protected $filter;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->noteAssociationHelper = $this
            ->getMockBuilder('Oro\Bundle\NoteBundle\Tools\NoteAssociationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('isNewEntity')
            ->willReturnCallback(function ($entity) {
                if (method_exists($entity, 'getId')) {
                    return !(bool)$entity->getId();
                }

                throw new \RuntimeException('Something wrong');
            });

        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->willReturnCallback(function ($entity) {
                return !$entity instanceof \stdClass;
            });

        $this->filter = new PlaceholderFilter(
            $this->noteAssociationHelper,
            $this->doctrineHelper
        );
    }

    protected function tearDown()
    {
        unset($this->noteAssociationHelper, $this->doctrineHelper, $this->filter);
    }

    public function testIsNoteAssociationEnabledWithNonManagedEntity()
    {
        $testEntity = new \stdClass();

        $this->noteAssociationHelper->expects($this->never())
            ->method('isNoteAssociationEnabled');

        $this->assertFalse($this->filter->isNoteAssociationEnabled($testEntity));
    }

    public function testIsNoteAssociationEnabledWithNull()
    {
        $this->noteAssociationHelper->expects($this->never())
            ->method('isNoteAssociationEnabled');

        $this->assertFalse(
            $this->filter->isNoteAssociationEnabled(null)
        );
    }

    public function testIsNoteAssociationEnabledWithNotObject()
    {
        $this->noteAssociationHelper->expects($this->never())
            ->method('isNoteAssociationEnabled');

        $this->assertFalse($this->filter->isNoteAssociationEnabled('test'));
    }

    public function testIsNoteAssociationEnabledWithNewEntity()
    {
        $this->noteAssociationHelper->expects($this->never())
            ->method('isNoteAssociationEnabled');

        $this->assertFalse(
            $this->filter->isNoteAssociationEnabled(new TestEntity(null))
        );
    }

    public function testIsNoteAssociationEnabledWithAssociationDisabled()
    {
        $this->noteAssociationHelper->expects($this->once())
            ->method('isNoteAssociationEnabled')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->filter->isNoteAssociationEnabled(new TestEntity(1))
        );
    }

    public function testIsNoteAssociationEnabled()
    {
        $this->noteAssociationHelper->expects($this->once())
            ->method('isNoteAssociationEnabled')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue(true));

        $this->assertTrue(
            $this->filter->isNoteAssociationEnabled(new TestEntity(1))
        );
    }
}
