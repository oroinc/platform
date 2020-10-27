<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\EventListener\OAuthSubscriber;
use Oro\Bundle\ImapBundle\Manager\OAuth2ManagerRegistry;
use Symfony\Component\Form\FormEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class OAuthSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var  OAuthSubscriber */
    protected $listener;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OAuth2ManagerRegistry */
    protected $oauthManagerRegistry;

    protected function setUp(): void
    {
        $this->translator = $this->createMock('Symfony\Contracts\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans');

        $this->oauthManagerRegistry = $this->getMockBuilder(OAuth2ManagerRegistry::class)
            ->getMock();

        $this->listener = new OAuthSubscriber($this->translator, $this->oauthManagerRegistry);
    }

    public function testExtendForm()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->exactly(2))
            ->method('add')
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

        //test without origin
        $formEvent = new FormEvent($form, null);
        $this->listener->extendForm($formEvent);

        //test with empty origin
        $formEvent = new FormEvent($form, new UserEmailOrigin());
        $this->listener->extendForm($formEvent);
    }
}
