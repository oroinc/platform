<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\CommentBundle\Placeholder\CommentPlaceholderFilter;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;

class CommentPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var  CommentPlaceholderFilter */
    protected $filter;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->filter = new CommentPlaceholderFilter($this->configManager);
    }

    /**
     * @param mixed $entity
     * @param int $count
     * @param bool $applicable
     * @param bool $expected
     * @dataProvider commentProvider
     */
    public function testIsApplicable($entity, $count, $applicable, $expected)
    {
        $config =  $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->exactly($count))
            ->method('is')
            ->with('enabled')
            ->will($this->returnValue($applicable));
        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $provider->expects($this->exactly($count))
            ->method('getConfig')
            ->will($this->returnValue($config));
        $this->configManager->expects($this->exactly($count))
            ->method('getProvider')
            ->will($this->returnValue($provider));

        $this->assertEquals($expected, $this->filter->isApplicable($entity));
    }

    /**
     * @return array
     */
    public function commentProvider()
    {
        $applicableEntity = new ItemStub();
        $notApplicableEntity = new ItemStub();
        return [
            'is null' => [null, 0, false, false],
            'is null with enabled on' => [null, 0, true, false],
            'applicable entity' => [$applicableEntity, 1, true, true],
            'not applicable entity' => [$notApplicableEntity, 1, false, false],
        ];
    }
}
