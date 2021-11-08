<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Filter;

use Oro\Bundle\DashboardBundle\Filter\WidgetConfigVisibilityFilter;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Config\Resolver\ResolverInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class WidgetConfigVisibilityFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetConfigVisibilityFilter */
    private $widgetConfigVisibilityFilter;

    protected function setUp(): void
    {
        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->expects($this->any())
            ->method('resolve')
            ->willReturnCallback(function (array $value) {
                return [$value === ['@true']];
            });

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($acl) {
                return $acl === 'enabled_acl';
            });

        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturnCallback(function ($resource, $resourceType) {
                return
                    ('dashboard_widgets' === $resourceType && str_starts_with($resource, 'enabled'))
                    || str_starts_with($resource, 'widget.enabled');
            });

        $this->widgetConfigVisibilityFilter = new WidgetConfigVisibilityFilter(
            $authorizationChecker,
            $resolver,
            $featureChecker
        );
    }

    /**
     * @dataProvider filterConfigsProvider
     */
    public function testFilterConfigs(array $configs, ?string $widgetName, array $expectedConfigs)
    {
        $this->assertEquals(
            $expectedConfigs,
            $this->widgetConfigVisibilityFilter->filterConfigs($configs, $widgetName)
        );
    }

    public function filterConfigsProvider(): array
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
