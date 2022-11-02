<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Twig;

use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\FormBundle\Form\Twig\TwigTemplateRenderer;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

class TwigTemplateRendererTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(string $template, array $context, string $expectedResult): void
    {
        $formRenderer = $this->createMock(FormRenderer::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('has')
            ->with(FormRenderer::class)
            ->willReturn(true);
        $container->expects($this->any())
            ->method('get')
            ->with(FormRenderer::class)
            ->willReturn($formRenderer);

        $environment = new Environment(new ArrayLoader());
        $environment->addExtension(new FormExtension());
        $environment->addRuntimeLoader(new ContainerRuntimeLoader($container));
        $twigTemplateRenderer = new TwigTemplateRenderer($environment, $context);
        $result = $twigTemplateRenderer->render($template);

        $this->assertEquals($expectedResult, $result);
    }

    public function renderDataProvider(): array
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DataBlockExtension())
            ->getFormFactory();
        $builder = $factory->createNamedBuilder('form', FormType::class);
        $builder->add('oro_ui___application_url', FormType::class);

        $formView = $builder->getForm()->createView();

        return [
            'empty' => [
                'template' => '',
                'context' => [],
                'expectedResult' => '',
            ],
            'config field' => [
                'template' => '{{ form_row(form.children[\'oro_ui___application_url\']) }}',
                'context' => ['form' => $formView],
                'expectedResult' => '',
            ],
        ];
    }
}
