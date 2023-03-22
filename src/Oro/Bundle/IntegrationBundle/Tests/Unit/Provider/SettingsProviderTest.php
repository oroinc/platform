<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Provider\SettingsProvider;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\Bundles\TestBundle2\TestBundle2;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestService;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Resolver\SystemAwareResolver;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var SettingsProvider */
    private $settingsProvider;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('IntegrationSettingsProvider');

        $this->container = $this->createMock(ContainerInterface::class);
        $resolver = new SystemAwareResolver();
        $resolver->setContainer($this->container);

        $this->settingsProvider = new SettingsProvider($cacheFile, false, $resolver);

        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);
    }

    /**
     * @dataProvider getFormSettingsDataProvider
     */
    public function testGetFormSettings($integrationType, $resolvedValue, $expectedResult)
    {
        $this->container->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['test.client', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, new TestService($resolvedValue)]
            ]);

        $this->assertSame(
            $expectedResult,
            $this->settingsProvider->getFormSettings('synchronization_settings', $integrationType)
        );
    }

    public function getFormSettingsDataProvider(): array
    {
        return [
            'should return all fields'                            => [
                'integrationType' => 'simple',
                'resolvedValue'   => true,
                'expectedResult'  => [
                    'enabled'      => [
                        'type'       => 'choice',
                        'priority'   => -200,
                        'options'    => ['choices' => ['Enabled' => 0, 'Disabled' => 1]],
                        'applicable' => []
                    ],
                    'schedule'     => [
                        'type'       => 'schedule_form_type',
                        'applicable' => [true],
                        'options'    => []
                    ],
                    'some_setting' => [
                        'type'       => 'choice',
                        'applicable' => ['simple'],
                        'priority'   => 200,
                        'options'    => []
                    ]
                ]
            ],
            'should not return field depends on resolved value'   => [
                'integrationType' => 'simple',
                'resolvedValue'   => false,
                'expectedResult'  => [
                    'enabled'      => [
                        'type'       => 'choice',
                        'priority'   => -200,
                        'options'    => ['choices' => ['Enabled' => 0, 'Disabled' => 1]],
                        'applicable' => []
                    ],
                    'some_setting' => [
                        'type'       => 'choice',
                        'applicable' => ['simple'],
                        'priority'   => 200,
                        'options'    => []
                    ]
                ]
            ],
            'should not return field depends on integration type' => [
                'integrationType' => 'other',
                'resolvedValue'   => true,
                'expectedResult'  => [
                    'enabled'  => [
                        'type'       => 'choice',
                        'priority'   => -200,
                        'options'    => ['choices' => ['Enabled' => 0, 'Disabled' => 1]],
                        'applicable' => []
                    ],
                    'schedule' => [
                        'type'       => 'schedule_form_type',
                        'applicable' => [true],
                        'options'    => []
                    ]
                ]
            ]
        ];
    }

    public function testGetFormSettingsForUndefinedForm()
    {
        $this->assertSame(
            [],
            $this->settingsProvider->getFormSettings('other', 'simple')
        );
    }
}
