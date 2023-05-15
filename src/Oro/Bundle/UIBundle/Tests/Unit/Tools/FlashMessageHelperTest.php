<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools;

use Oro\Bundle\UIBundle\Tools\FlashMessageHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlashMessageHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagHelper;

    /** @var FlashMessageHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);
        $this->helper = new FlashMessageHelper($this->requestStack, $this->translator, $this->htmlTagHelper);
    }

    public function testAddFlashMessage()
    {
        $type = 'info';
        $message = 'some.test.message.key';
        $params = [
            'param1' => 'value1',
            'param2' => 'value2',
        ];
        $domain = 'test_domain';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with($message, $params, $domain)
            ->willReturn('translated_message');

        $this->htmlTagHelper->expects($this->once())
            ->method('sanitize')
            ->with('translated_message')
            ->willReturn('sanitized_message');

        $flashBag = new FlashBag();

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->helper->addFlashMessage($type, $message, $params, $domain);

        $this->assertEquals(
            [
                'info' => [
                    'sanitized_message'
                ]
            ],
            $flashBag->all()
        );
    }
}
