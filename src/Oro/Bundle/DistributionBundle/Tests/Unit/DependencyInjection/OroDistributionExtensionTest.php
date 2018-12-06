<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DistributionBundle\DependencyInjection\OroDistributionExtension;
use Oro\Bundle\DistributionBundle\Form\Type\Composer\ConfigType;
use Oro\Bundle\DistributionBundle\Form\Type\Composer\RepositoryType;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Bundle\DistributionBundle\Routing\OroAutoLoader;
use Oro\Bundle\DistributionBundle\Routing\OroExposeLoader;
use Oro\Bundle\DistributionBundle\Script\Runner;
use Oro\Bundle\DistributionBundle\Security\AccessDeniedListener;
use Oro\Bundle\DistributionBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroDistributionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $containerBuilder;

    /** @var OroDistributionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);

        $this->extension = new OroDistributionExtension();
    }

    /**
     * @dataProvider loadDataProvider
     *
     * @param array $kernelBundles
     * @param array $expectedParameters
     */
    public function testLoad(array $kernelBundles, array $expectedParameters)
    {
        $this->containerBuilder->expects($this->any())
            ->method('getParameter')
            ->willReturnMap(
                [
                    ['kernel.bundles', $kernelBundles],
                ]
            );

        $parameters = [];

        $this->containerBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(
                function ($name, $value) use (&$parameters) {
                    $parameters[$name] = $value;
                }
            );

        $this->extension->load([], $this->containerBuilder);

        $this->assertEquals($expectedParameters, $parameters);
    }

    /**
     * @return \Generator
     */
    public function loadDataProvider()
    {
        $parameters = [
            'oro_distribution.routing_loader.class' => OroAutoLoader::class,
            'oro_distribution.expose_routing_loader.class' => OroExposeLoader::class,
            'oro_distribution.package_manager.class' => PackageManager::class,
            'oro_distribution.script_runner.class' => Runner::class,
            'oro_distribution.composer.class' => 'Composer\Composer',
            'oro_distribution.composer.io.class' => 'Composer\IO\BufferIO',
            'oro_distribution.composer.installer.class' => 'Composer\Installer',
            'oro_distribution.composer.installation_manager.class' => 'Composer\Installer\InstallationManager',
            'oro_distribution.composer.json_file.class' => 'Composer\Json\JsonFile',
            'oro_distribution.composer_json' => '%kernel.project_dir%/composer.json',
            'oro_distribution.security.access_denied_listener.class' => AccessDeniedListener::class,
            'oro_distribution.form.type.composer_config.class' => ConfigType::class,
            'oro_distribution.form.type.composer_repository.class' => RepositoryType::class,
            'oro_distribution.package_manager.system_paths' => [
                'vendor',
                'public/bundles',
                'public/js',
                'public/css',
                'composer.json',
            ],
            'twig.form.resources' => [],
            'oro_distribution.composer_cache_home' => '%kernel.project_dir%/var/cache/composer'
        ];

        yield 'with OroTranslationBundle' => [
            'kernelBundles' => [
                'OroTranslationBundle' => 'Oro\Bundle\TranslationBundle\OroTranslationBundle'
            ],
            'expectedParameters' => array_merge(
                $parameters,
                [
                    'twig.form.resources' => [
                        'OroTranslationBundle:Form:fields.html.twig'
                    ]
                ]
            )
        ];

        yield 'without OroTranslationBundle' => [
            'kernelBundles' => [],
            'expectedParameters' => array_merge($parameters, ['translator.class' => Translator::class])
        ];
    }
}
