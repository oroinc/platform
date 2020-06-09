<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\EmailBundle\Model\DTO\EmailAddressDTO;
use Oro\Bundle\EmailBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;

class LocalizedTemplateDTOTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailTemplate */
    private $emailTemplate;

    /** @var LocalizedTemplateDTO */
    private $dto;

    protected function setUp(): void
    {
        $this->emailTemplate = new EmailTemplate();

        $this->dto = new LocalizedTemplateDTO($this->emailTemplate);
    }

    public function testGetEmailTemplate(): void
    {
        $this->assertSame($this->emailTemplate, $this->dto->getEmailTemplate());
    }

    public function testRecipientsAndEmails(): void
    {
        $rcpt1 = new EmailAddressDTO('test1@example.com');
        $rcpt2 = new EmailAddressDTO('test2@example.com');
        $rcpt3 = new EmailAddressDTO('test3@example.com');

        $this->dto->addRecipient($rcpt1);
        $this->dto->addRecipient($rcpt2);
        $this->dto->addRecipient($rcpt3);

        $this->assertSame([$rcpt1, $rcpt2, $rcpt3], $this->dto->getRecipients());
        $this->assertSame([$rcpt1->getEmail(), $rcpt2->getEmail(), $rcpt3->getEmail()], $this->dto->getEmails());
    }
}
