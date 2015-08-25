<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Placeholder;

use Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestNonManagedTarget;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\NoteBundle\Tests\Unit\Fixtures\TestEntity;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_REFERENCE = 'Oro\Bundle\NoteBundle\Tests\Unit\Fixtures\TestEntity';

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider */
    protected $noteConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider */
    protected $entityConfigProvider;

    /** @var PlaceholderFilter */
    protected $filter;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->noteConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
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
                return !$entity instanceof TestNonManagedTarget;
            });

        $this->filter = new PlaceholderFilter(
            $this->noteConfigProvider,
            $this->entityConfigProvider,
            $this->doctrineHelper
        );
    }

    protected function tearDown()
    {
        unset($this->noteConfigProvider, $this->entityConfigProvider, $this->doctrineHelper, $this->filter);
    }

    public function testIsNoteAssociationEnabledWithNonManagedEntity()
    {
        $testEntity = new TestNonManagedTarget(1);
        $this->assertFalse($this->filter->isNoteAssociationEnabled($testEntity));
    }

    public function testIsNoteAssociationEnabledWithNull()
    {
        $this->noteConfigProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->filter->isNoteAssociationEnabled(null)
        );
    }

    public function testIsNoteAssociationEnabledWithNotObject()
    {
        $this->noteConfigProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse($this->filter->isNoteAssociationEnabled('test'));
    }

    public function testIsNoteAssociationEnabledWithoutFilledId()
    {
        $this->assertFalse(
            $this->filter->isNoteAssociationEnabled(new TestEntity(null))
        );
    }

    public function testIsNoteAssociationEnabledWithNotConfigurableEntity()
    {
        $this->noteConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue(false));

        $this->assertFalse($this->filter->isNoteAssociationEnabled(new TestEntity(1)));
    }

    public function testIsNoteAssociationEnabledWithNotUpdatedSchema()
    {
        $config = new Config(new EntityConfigId('note', static::TEST_ENTITY_REFERENCE));
        $config->set('enabled', true);

        $this->noteConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue(true));
        $this->noteConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue($config));
        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(Note::ENTITY_NAME, ExtendHelper::buildAssociationName(static::TEST_ENTITY_REFERENCE))
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->filter->isNoteAssociationEnabled(new TestEntity(1))
        );
    }

    public function testIsNoteAssociationEnabled()
    {
        $config = new Config(new EntityConfigId('note', static::TEST_ENTITY_REFERENCE));
        $config->set('enabled', true);

        $this->noteConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue(true));
        $this->noteConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue($config));
        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(Note::ENTITY_NAME, ExtendHelper::buildAssociationName(static::TEST_ENTITY_REFERENCE))
            ->will($this->returnValue(true));

        $this->assertTrue(
            $this->filter->isNoteAssociationEnabled(new TestEntity(1))
        );
    }
}
