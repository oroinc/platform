<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Event\EmailTemplateContextCollectEvent;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateContextProvider;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EmailTemplateContextProviderTest extends TestCase
{
    private EventDispatcherInterface|MockObject $eventDispatcher;

    private EmailTemplateContextProvider $provider;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->provider = new EmailTemplateContextProvider($this->eventDispatcher);
    }

    public function testGetTemplateContextWhenSingleRecipient(): void
    {
        $from = From::emailAddress('no-reply@example.com');
        $recipient = new UserStub(42);
        $templateName = 'sample_template_name';
        $emailTemplateCriteria = new EmailTemplateCriteria('sample_template_name');
        $templateParams = ['entity' => $recipient];

        $event = new EmailTemplateContextCollectEvent($from, [$recipient], $emailTemplateCriteria, $templateParams);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(static function (EmailTemplateContextCollectEvent $event) {
                $event->setTemplateContextParameter('new_key', 'new_value');

                return $event;
            });

        self::assertEquals(
            ['new_key' => 'new_value'],
            $this->provider->getTemplateContext($from, $recipient, $templateName, $templateParams)
        );
    }

    public function testGetTemplateContextWhenTemplateName(): void
    {
        $from = From::emailAddress('no-reply@example.com');
        $recipients = [new UserStub(42)];
        $templateName = 'sample_template_name';
        $emailTemplateCriteria = new EmailTemplateCriteria('sample_template_name');
        $templateParams = ['entity' => new UserStub(42)];

        $event = new EmailTemplateContextCollectEvent($from, $recipients, $emailTemplateCriteria, $templateParams);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(static function (EmailTemplateContextCollectEvent $event) {
                $event->setTemplateContextParameter('new_key', 'new_value');

                return $event;
            });

        self::assertEquals(
            ['new_key' => 'new_value'],
            $this->provider->getTemplateContext($from, $recipients, $templateName, $templateParams)
        );
    }

    public function testGetTemplateContextWhenEmailTemplateCriteria(): void
    {
        $from = From::emailAddress('no-reply@example.com');
        $recipients = [new UserStub(42)];
        $emailTemplateCriteria = new EmailTemplateCriteria(
            'sample_template_name',
        );
        $templateParams = ['entity' => new UserStub(42)];

        $event = new EmailTemplateContextCollectEvent($from, $recipients, $emailTemplateCriteria, $templateParams);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(static function (EmailTemplateContextCollectEvent $event) {
                $event->setTemplateContextParameter('new_key', 'new_value');

                return $event;
            });

        self::assertEquals(
            ['new_key' => 'new_value'],
            $this->provider->getTemplateContext($from, $recipients, $emailTemplateCriteria, $templateParams)
        );
    }
}
