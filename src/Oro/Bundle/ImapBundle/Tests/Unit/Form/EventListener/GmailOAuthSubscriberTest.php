<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\EventListener\GmailOAuthSubscriber;
use Symfony\Component\Form\FormEvent;

class GmailOAuthSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var  GmailOAuthSubscriber */
    protected $listener;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
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
}
