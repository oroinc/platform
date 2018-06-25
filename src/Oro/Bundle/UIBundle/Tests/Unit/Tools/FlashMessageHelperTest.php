<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools;

use Oro\Bundle\UIBundle\Tools\FlashMessageHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class FlashMessageHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    protected $session;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $htmlTagHelper;

    /** @var FlashMessageHelper */
    protected $helper;

    protected function setUp()
    {
        $this->session = $this->createMock(Session::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $this->helper = new FlashMessageHelper($this->session, $this->translator, $this->htmlTagHelper);
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
