<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DistributionBundle\DependencyInjection\OroDistributionExtension;
use Oro\Bundle\DistributionBundle\Translation\Translator;
use Oro\Bundle\TranslationBundle\OroTranslationBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroDistributionExtensionTest extends \PHPUnit\Framework\TestCase
{
    private OroDistributionExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new OroDistributionExtension();
    }

    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $kernelBundles, array $expectedParameters): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.bundles', $kernelBundles);
        $containerBuilder->setParameter('twig.form.resources', []);
        $this->extension->load([], $containerBuilder);

        self::assertEquals($expectedParameters, $containerBuilder->getParameterBag()->all());
    }

    public function loadDataProvider(): array
    {
        $parameters = [
            'twig.form.resources' => [],
            'oro_distribution.composer_json' => '%kernel.project_dir%/composer.json',
            'container.dumper.inline_factories' => true,
        ];

        return [
            'with OroTranslationBundle' => [
                'kernelBundles' => ['OroTranslationBundle' => OroTranslationBundle::class],
                'expectedParameters' => array_merge(
                    $parameters,
                    [
                        'kernel.bundles' => ['OroTranslationBundle' => OroTranslationBundle::class],
                        'twig.form.resources' => [
                            '@OroTranslation/Form/fields.html.twig',
                        ],
                    ]
                ),
            ],
            'without OroTranslationBundle' => [
                'kernelBundles' => [],
                'expectedParameters' => array_merge(
                    $parameters,
                    ['kernel.bundles' => [], 'translator.class' => Translator::class]
                ),
            ],
        ];
    }
}
