<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Filter;

use Oro\Bundle\DashboardBundle\Filter\WidgetConfigVisibilityFilter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class WidgetConfigVisibilityFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetConfigVisibilityFilter */
    protected $widgetConfigVisibilityFilter;

    protected function setUp()
    {
        $resolver = $this->createMock('Oro\Component\Config\Resolver\ResolverInterface');
        $resolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnCallback(function (array $value) {
                return [$value === ['@true']];
            }));

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->will($this->returnCallback(function ($acl) {
                return $acl === 'enabled_acl';
            }));

        $featureChecker = $this->getMockBuilder('Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker')
            ->disableOriginalConstructor()
            ->getMock();
        $featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->will($this->returnCallback(function ($resource, $resourceType) {
                return $resourceType === 'dashboard_widgets' &&
                    strpos($resource, 'enabled') === 0 || strpos($resource, 'widget.enabled') === 0;
            }));

        $this->widgetConfigVisibilityFilter = new WidgetConfigVisibilityFilter(
            $authorizationChecker,
            $resolver,
            $featureChecker
        );
    }

    /**
     * @dataProvider filterConfigsProvider
     */
    public function testFilterConfigs(array $configs, $widgetName, $expectedConfigs)
    {
        $this->assertEquals(
            $expectedConfigs,
            $this->widgetConfigVisibilityFilter->filterConfigs($configs, $widgetName)
        );
    }

    public function filterConfigsProvider()
    {
        return [
            'widgets' => [
                [
                    'enabled with minimal config' => [
                        'enabled' => true,
                    ],
                    'disabled acl' => [
                        'enabled'    => true,
                        'acl'        => 'disabled_acl',
                    ],
                    'non applicable' => [
                        'enabled'    => true,
                        'applicable' => '@false',
                    ],
                    'enabled with everything enabled' => [
                        'enabled'    => true,
                        'acl'        => 'enabled_acl',
                        'applicable' => '@true',
                    ],
                    'enabled with everything enabled with additional config' => [
                        'enabled'    => true,
                        'acl'        => 'enabled_acl',
                        'applicable' => '@true',
                        'additional1' => 'value1',
                        'additional2' => 'value2',
                    ],
                ],
                null,
                [
                    'enabled with minimal config' => [],
                    'enabled with everything enabled' => [],
                    'enabled with everything enabled with additional config' => [
                        'additional1' => 'value1',
                        'additional2' => 'value2',
                    ],
                ],
            ],
            'sub widgets' => [
                [
                    'enabled with minimal config' => [
                        'enabled' => true,
                    ],
                    'disabled acl' => [
                        'enabled'    => true,
                        'acl'        => 'disabled_acl',
                    ],
                    'non applicable' => [
                        'enabled'    => true,
                        'applicable' => '@false',
                    ],
                    'enabled with everything enabled' => [
                        'enabled'    => true,
                        'acl'        => 'enabled_acl',
                        'applicable' => '@true',
                    ],
                    'enabled with everything enabled with additional config' => [
                        'enabled'    => true,
                        'acl'        => 'enabled_acl',
                        'applicable' => '@true',
                        'additional1' => 'value1',
                        'additional2' => 'value2',
                    ],
                ],
                'widget',
                [
                    'enabled with minimal config' => [],
                    'enabled with everything enabled' => [],
                    'enabled with everything enabled with additional config' => [
                        'additional1' => 'value1',
                        'additional2' => 'value2',
                    ],
                ],
            ],
        ];
    }
}
