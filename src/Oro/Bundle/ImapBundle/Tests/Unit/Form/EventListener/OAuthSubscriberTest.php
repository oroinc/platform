<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\EventListener\OAuthSubscriber;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class OAuthSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var OAuthManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $oauthManagerRegistry;

    /** @var OAuthSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->oauthManagerRegistry = $this->createMock(OAuthManagerRegistry::class);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . '_translated';
            });

        $this->subscriber = new OAuthSubscriber($this->translator, $this->oauthManagerRegistry);
    }

    public function testExtendForm(): void
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->exactly(2))
            ->method('add')
            ->willReturnSelf();

        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setAccessToken('test_string_token');

        $formEvent = new FormEvent($form, $userEmailOrigin);
        $this->subscriber->extendForm($formEvent);
    }

    public function testExtendFormWithEmptyOrigin(): void
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->never())
            ->method('add')
            ->willReturnSelf();

        //test without origin
        $formEvent = new FormEvent($form, null);
        $this->subscriber->extendForm($formEvent);

        //test with empty origin
        $formEvent = new FormEvent($form, new UserEmailOrigin());
        $this->subscriber->extendForm($formEvent);
    }
}
