<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\Action;

use Oro\Bundle\EmailBundle\Model\Action\StripHtmlTags;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StripHtmlTagsTest extends TestCase
{
    private ContextAccessor&MockObject $contextAccessor;
    private HtmlTagHelper&MockObject $helper;
    private StripHtmlTags $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->helper = $this->createMock(HtmlTagHelper::class);
        $this->action = new StripHtmlTags($this->contextAccessor, $this->helper);

        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitializeWithNamedOptions(): void
    {
        $options = [
            'html' => '$.html',
            'attribute' => '$.attribute'
        ];

        $this->action->initialize($options);

        $this->assertEquals('$.html', ReflectionUtil::getPropertyValue($this->action, 'html'));
        $this->assertEquals('$.attribute', ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithArrayOptions(): void
    {
        $options = [
            '$.attribute',
            '$.html'
        ];

        $this->action->initialize($options);

        $this->assertEquals('$.html', ReflectionUtil::getPropertyValue($this->action, 'html'));
        $this->assertEquals('$.attribute', ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithMissingOption(): void
    {
        $this->expectException(InvalidParameterException::class);
        $options = [
            '$.attribute'
        ];

        $this->action->initialize($options);
    }

    public function testExecuteAction(): void
    {
        $options = [
            'html' => '$.html',
            'attribute' => '$.attribute'
        ];

        $fakeContext = ['fake', 'things', 'are', 'here'];

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->with($fakeContext, '$.html')
            ->willReturn($html = '<html></html>');

        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with($fakeContext, '$.attribute', $stripped = 'stripped');

        $this->helper->expects($this->once())
            ->method('purify')
            ->with($html)
            ->willReturn($purified = 'purified');

        $this->helper->expects($this->once())
            ->method('stripTags')
            ->with($purified)
            ->willReturn($stripped);

        $this->action->initialize($options);
        $this->action->execute($fakeContext);
    }
}
