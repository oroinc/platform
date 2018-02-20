<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;
use Oro\Bundle\UIBundle\Twig\PlaceholderExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

class PlaceholderExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    const PLACEHOLDER_NAME = 'placeholder_name';
    const INVALID_PLACEHOLDER_NAME = 'invalid_placeholder_name';
    const TEMPLATE_NAME = 'FooBarBundle:Test:test.html.twig';
    const ACTION_NAME = 'FooBarBundle:Test:test';
    const DELIMITER = '<br/>';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $environment;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $placeholderProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $kernelExtension;

    /** @var PlaceholderExtension */
    protected $extension;

    /** @var array */
    protected $placeholders = [
        self::PLACEHOLDER_NAME => [
            'items' => [
                ['template' => self::TEMPLATE_NAME],
                ['action' => self::ACTION_NAME],
            ]
        ],
        self::INVALID_PLACEHOLDER_NAME => [
            'items' => [
                ['foo' => 'bar', 'baz' => 'bar'],
            ]
        ],
    ];

    protected function setUp()
    {
        $this->environment = $this->createMock(\Twig_Environment::class);
        $this->placeholderProvider = $this->getMockBuilder(PlaceholderProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->kernelExtension = $this->getMockBuilder(HttpKernelExtension::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_ui.placeholder.provider', $this->placeholderProvider)
            ->add('request_stack', $this->requestStack)
            ->getContainer($this);

        $this->extension = new PlaceholderExtension($container);
    }

    public function testRenderPlaceholder()
    {
        $variables = ['variables' => 'test'];
        $query = ['key' => 'value'];
        $expectedTemplateRender = '<p>template</p>';
        $expectedActionRender = '<p>action</p>';
        $expectedResult = $expectedTemplateRender . self::DELIMITER . $expectedActionRender;

        $request = new Request();
        $request->query->add($query);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->placeholderProvider->expects($this->once())
            ->method('getPlaceholderItems')
            ->with(self::PLACEHOLDER_NAME, $variables)
            ->will($this->returnValue($this->placeholders[self::PLACEHOLDER_NAME]['items']));

        $this->environment->expects($this->once())
            ->method('render')
            ->with(self::TEMPLATE_NAME, $variables)
            ->will($this->returnValue($expectedTemplateRender));
        $this->environment->expects($this->once())
            ->method('getExtension')
            ->with(HttpKernelExtension::class)
            ->willReturn($this->kernelExtension);

        $controllerReference = $this->getMockBuilder(ControllerReference::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->kernelExtension->expects($this->once())
            ->method('controller')
            ->with(self::ACTION_NAME, $variables, $query)
            ->will($this->returnValue($controllerReference));

        $this->kernelExtension->expects($this->once())
            ->method('renderFragment')
            ->with($controllerReference)
            ->will($this->returnValue($expectedActionRender));

        $result = self::callTwigFunction(
            $this->extension,
            'placeholder',
            [
                $this->environment,
                self::PLACEHOLDER_NAME,
                $variables,
                ['delimiter' => self::DELIMITER]
            ]
        );

        $this->assertEquals($expectedResult, $result);
    }

    //@codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot render placeholder item with keys "bar", "bar". Expects "template" or "action" key.
     */
    //@codingStandardsIgnoreEnd
    public function testRenderPlaceholderFails()
    {
        $this->placeholderProvider->expects($this->once())
            ->method('getPlaceholderItems')
            ->with(self::INVALID_PLACEHOLDER_NAME, [])
            ->will($this->returnValue($this->placeholders[self::INVALID_PLACEHOLDER_NAME]['items']));

        self::callTwigFunction(
            $this->extension,
            'placeholder',
            [
                $this->environment,
                self::INVALID_PLACEHOLDER_NAME,
                [],
                ['delimiter' => self::DELIMITER]
            ]
        );
    }

    public function testGetName()
    {
        $this->assertEquals(PlaceholderExtension::EXTENSION_NAME, $this->extension->getName());
    }
}
