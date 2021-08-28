<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Model\RecipientEntity;

class RecipientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider recipientProvider
     */
    public function testGetName(string $email, string $name, string $label, string $expected)
    {
        $recipientEntity = new RecipientEntity('class', 'id', $label, 'org');

        $recipient = new Recipient($email, $name, $recipientEntity);
        $this->assertEquals($expected, $recipient->getName());
    }

    public static function recipientProvider(): array
    {
        return [
            ['john@example.com', 'john@example.com', '', 'john@example.com'],
            ['john@example.com', '<john@example.com>', '', '<john@example.com>'],
            ['john@example.com', 'John Smith <john@example.com>', 'John Smith', 'John Smith <john@example.com>'],
            ['john@example.com', '"John Smith" <john@example.com>', 'John Smith', '"John Smith" <john@example.com>'],
            ['john@example.com', '\'John \' <john@example.com>', 'John ', '\'John \' <john@example.com>'],
            ['john@example.com', '"john@example.com" <john@example.com>', '', '"john@example.com" <john@example.com>'],
            ['john@example.com', 'John <john@example.com>', 'John (Contact)', 'John <john@example.com> (Contact)'],
            ['john@example.com', '"John" <john@example.com>', 'John (Contact)', '"John" <john@example.com> (Contact)'],
            ['john@example.com', '<john@example.com>', 'john (Contact)', '<john@example.com> (Contact)'],
            ['john@example.com', 'john@example.com', '(Contact)', '<john@example.com> (Contact)'],
            ['john@example.com', 'john@example.com', '(org)', '<john@example.com> (org)']
        ];
    }

    /**
     * @dataProvider recipientLabelProvider
     */
    public function testGetLabel(string $email, string $name, string $label, string $organization, string $expected)
    {
        $recipientEntity = new RecipientEntity('class', 'id', $label, $organization);

        $recipient = new Recipient($email, $name, $recipientEntity);
        $this->assertEquals($expected, $recipient->getLabel());
    }

    public static function recipientLabelProvider(): array
    {
        return [
            ['john@example.com', 'john@example.com', '', '', 'john@example.com'],
            ['john@example.com', '<john@example.com>', '', '', '<john@example.com>'],
            ['john@example.com', '<john@example.com>', 'john (Contact)', '', '<john@example.com> (Contact)'],
            ['john@example.com', 'john@example.com', '(Contact)', 'Org', '<john@example.com> (Org Contact)'],
            ['john@example.com', 'john@example.com', '', 'Org', '<john@example.com> (Org)']
        ];
    }
}
