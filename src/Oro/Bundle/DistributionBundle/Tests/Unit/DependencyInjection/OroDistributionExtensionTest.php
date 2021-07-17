<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DistributionBundle\DependencyInjection\OroDistributionExtension;
use Oro\Bundle\DistributionBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroDistributionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $containerBuilder;

    /** @var OroDistributionExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);

        $this->extension = new OroDistributionExtension();
    }

    /**
     * @dataProvider loadDataProvider
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
            'oro_distribution.composer_json' => '%kernel.project_dir%/composer.json',
            'twig.form.resources' => [],
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
