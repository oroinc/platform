<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\EventListener\GmailOAuthSubscriber;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Translation\TranslatorInterface;

class GmailOAuthSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var  GmailOAuthSubscriber */
    protected $listener;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected function setUp()
    {
        $this->translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans');

        $this->listener = new GmailOAuthSubscriber($this->translator);
    }

    public function testExtendForm()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->exactly(2))
            ->method('add')
            ->will($this->returnSelf());
        $form->expects($this->once())
            ->method('remove')
            ->will($this->returnSelf());

        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setAccessToken('test_string_token');

        $formEvent = new FormEvent($form, $userEmailOrigin);
        $this->listener->extendForm($formEvent);
    }

    public function testExtendFormWithEmptyOrigin()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->never())
            ->method('add')
            ->will($this->returnSelf());
        $form->expects($this->never())
            ->method('remove')
            ->will($this->returnSelf());

        //test without origin
        $formEvent = new FormEvent($form, null);
        $this->listener->extendForm($formEvent);

        //test with empty origin
        $formEvent = new FormEvent($form, new UserEmailOrigin());
        $this->listener->extendForm($formEvent);
    }
}
