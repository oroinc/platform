<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Placeholder;

use Oro\Bundle\EntityBundle\ORM\EntityClassAccessor;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\NoteBundle\Placeholder\Filter;

class FilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $noteConfigProvider;

    /** @var Filter */
    protected $filter;

    protected function setUp()
    {
        $this->noteConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new Filter(
            $this->noteConfigProvider,
            new EntityClassAccessor()
        );
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

        $this->assertFalse(
            $this->filter->isNoteAssociationEnabled('test')
        );
    }

    public function testIsNoteAssociationEnabledWithNotConfigurableEntity()
    {
        $this->noteConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('stdClass')
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->filter->isNoteAssociationEnabled(new \stdClass())
        );
    }

    public function testIsNoteAssociationEnabled()
    {
        $config = new Config(new EntityConfigId('note', 'stdClass'));
        $config->set('enabled', true);

        $this->noteConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('stdClass')
            ->will($this->returnValue(true));
        $this->noteConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('stdClass')
            ->will($this->returnValue($config));

        $this->assertTrue(
            $this->filter->isNoteAssociationEnabled(new \stdClass())
        );
    }
}
