<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Menu;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Menu\FeatureAwareMenuFactoryExtension;

class FeatureAwareMenuFactoryExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $featureChecker;

    /**
     * @var FeatureAwareMenuFactoryExtension
     */
    private $extension;

    protected function setUp()
    {
        /** @var FeatureChecker $featureChecker */
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)->disableOriginalConstructor()->getMock();

        $this->extension = new FeatureAwareMenuFactoryExtension($this->featureChecker);
    }

    public function testBuildOptionsChangeToNotAllowed()
    {
        $this->featureChecker
            ->expects($this->once())
            ->method('isResourceEnabled')
            ->with('route_name', 'routes')
            ->willReturn(false);

        $options = $this->extension->buildOptions(
            [
                'extras' => [
                    'isAllowed' => true,
                ],
                'route' => 'route_name',
            ]
        );

        $this->assertFalse($options['extras']['isAllowed']);
    }

    /**
     * @dataProvider optionsDataProvider
     *
     * @param array $options
     */
    public function testBuildOptionsNoChanges(array $options)
    {
        $this->featureChecker
            ->expects($this->never())
            ->method('isResourceEnabled');

        $this->extension->buildOptions($options);
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            [
                'options' => [],
            ],
            [
                'options' => [
                    'extras' => [
                        'isAllowed' => false,
                    ],
                    'route' => 'route_name',
                ],
            ],
            [
                'options' => [
                    'extras' => [
                        'isAllowed' => true,
                    ],
                ],
            ],
        ];
    }
}
