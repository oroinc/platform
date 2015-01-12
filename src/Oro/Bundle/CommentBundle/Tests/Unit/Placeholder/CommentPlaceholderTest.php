<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\CommentBundle\Placeholder\CommentPlaceholderFilter;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;

class CommentPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var  CommentPlaceholderFilter */
    protected $filter;

    protected function setUp()
    {
        $this->configManager  = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter         = new CommentPlaceholderFilter($this->configManager, $this->securityFacade);
    }

    /**
     * @param mixed $entity
     * @param int   $callsCount
     * @param int   $callsProviderCount
     * @param bool  $isApplicable
     * @param bool  $expected
     *
     * @dataProvider commentProvider
     */
    public function testIsApplicable($entity, $callsCount, $callsProviderCount, $isApplicable, $isGranted, $expected)
    {
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->exactly($callsProviderCount))
            ->method('is')
            ->will($this->returnValue($isApplicable));

        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $provider->expects($this->exactly($callsProviderCount))
            ->method('getConfig')
            ->will($this->returnValue($config));

        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->with('oro_comment_view')
            ->will($this->returnValue($isGranted));
        $this->configManager->expects($this->exactly($callsProviderCount))
            ->method('getProvider')
            ->will($this->returnValue($provider));
        $this->configManager->expects($this->exactly($callsCount))
            ->method('hasConfig')
            ->will($this->returnValue(true));

        $this->assertEquals($expected, $this->filter->isApplicable($entity));
    }

    /**
     * @return array
     */
    public function commentProvider()
    {
        $entity = new ItemStub();
        return [
            'is null'                 => [null, 0, 0, false, true, false],
            'is null with enabled on' => [null, 0, 0, true, true, false],
            'applicable entity'       => [$entity, 1, 2, true, true, true],
            'not applicable entity'   => [$entity, 1, 1, false, true, false],
        ];
    }
}
