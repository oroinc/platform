<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\RenderTemplate;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Twig\Environment;

class RenderTemplateTest extends \PHPUnit\Framework\TestCase
{
    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var RenderTemplate */
    private $action;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);

        $this->action = new RenderTemplate(new ContextAccessor(), $this->twig);
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitialize(): void
    {
        $options = [
            'attribute' => new PropertyPath('attribute'),
            'template' => new PropertyPath('template')
        ];

        $this->assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
    }

    public function testExecute(): void
    {
        $template = '@AcmeTest/template.html.twig';
        $html = '<h1>Test: 1, 2</h1>';

        $context = new ActionData([
            'template' => $template,

        ]);

        $this->twig->expects($this->once())
            ->method('render')
            ->with($template, ['one' => 1, 'two' => 2])
            ->willReturn($html);

        $this->action->initialize([
            'attribute' => new PropertyPath('attribute'),
            'template' => $template,
            'params' => [
                'one' => 1,
                'two' => 2
            ]
        ]);

        $this->action->execute($context);
        $this->assertSame($html, $context->get('attribute'));
    }
}
