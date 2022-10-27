<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\EmailBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\Recipient;

class LocalizedTemplateDTOTest extends \PHPUnit\Framework\TestCase
{
    private EmailTemplate $emailTemplate;

    private LocalizedTemplateDTO $dto;

    protected function setUp(): void
    {
        $this->emailTemplate = new EmailTemplate();

        $this->dto = new LocalizedTemplateDTO($this->emailTemplate);
    }

    public function testGetEmailTemplate(): void
    {
        self::assertSame($this->emailTemplate, $this->dto->getEmailTemplate());
    }

    public function testRecipientsAndEmails(): void
    {
        $rcpt1 = new Recipient('test1@example.com');
        $rcpt2 = new Recipient('test2@example.com');
        $rcpt3 = new Recipient('test3@example.com');

        $this->dto->addRecipient($rcpt1);
        $this->dto->addRecipient($rcpt2);
        $this->dto->addRecipient($rcpt3);

        self::assertSame([$rcpt1, $rcpt2, $rcpt3], $this->dto->getRecipients());
        self::assertSame([$rcpt1->getEmail(), $rcpt2->getEmail(), $rcpt3->getEmail()], $this->dto->getEmails());
    }
}
