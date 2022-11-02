<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Cache;

use Oro\Bundle\LayoutBundle\Cache\TwigTemplateCacheWarmer;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Template;

class TwigTemplateCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    private function getCacheWarmer(Environment $twig, iterable $iterator): TwigTemplateCacheWarmer
    {
        $container = TestContainerBuilder::create()
            ->add('twig', $twig)
            ->getContainer($this);

        return new TwigTemplateCacheWarmer($container, $iterator);
    }

    public function testWarmUp(): void
    {
        $twig = $this->createMock(Environment::class);
        $template = $this->createMock(Template::class);
        $iterator = [
            'template.html.twig',
            'invalid_template.html.twig',
            '@Acme/template.html.twig',
            '@Acme/another.yml',
            '@Acme/another.jpg',
            '@Acme/layouts/template.html.twig',
            '@Acme/layouts/update.yml',
            '@Acme/layouts/another.jpg',
            '@Acme/layouts/theme/template.html.twig',
            '@Acme/layouts/theme/update.yml',
            '@Acme/layouts/theme/another.jpg'
        ];

        $twig->expects(self::exactly(9))
            ->method('load')
            ->withConsecutive(
                ['template.html.twig'],
                ['invalid_template.html.twig'],
                ['@Acme/template.html.twig'],
                ['@Acme/another.yml'],
                ['@Acme/another.jpg'],
                ['@Acme/layouts/template.html.twig'],
                ['@Acme/layouts/another.jpg'],
                ['@Acme/layouts/theme/template.html.twig'],
                ['@Acme/layouts/theme/another.jpg']
            )
            ->willReturnCallback(function (string $templateName) use ($twig, $template) {
                if ('invalid_template.html.twig' === $templateName) {
                    throw new Error('some error');
                }
                return new \Twig\TemplateWrapper($twig, $template);
            });

        $cacheWarmer = $this->getCacheWarmer($twig, $iterator);
        $cacheWarmer->warmUp('');
    }

    public function testIsOptional(): void
    {
        $cacheWarmer = $this->getCacheWarmer($this->createMock(Environment::class), []);
        self::assertTrue($cacheWarmer->isOptional());
    }
}
