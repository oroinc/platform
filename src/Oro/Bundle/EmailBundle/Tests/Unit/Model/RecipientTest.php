<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Model\RecipientEntity;

class RecipientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string $email
     * @param string $name
     * @param string $label
     * @param string $expected
     *
     * @dataProvider recipientProvider
     */
    public function testGetName($email, $name, $label, $expected)
    {
        /** @var RecipientEntity $recipientEntity */
        $recipientEntity = new RecipientEntity(
            'class',
            'id',
            $label,
            'org'
        );

        $recipient = new Recipient($email, $name, $recipientEntity);
        $this->assertEquals($expected, $recipient->getName());
    }

    public static function recipientProvider()
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
     * @param string $email
     * @param string $name
     * @param string $label
     * @param string $organization
     * @param string $expected
     *
     * @dataProvider recipientLabelProvider
     */
    public function testGetLabel($email, $name, $label, $organization, $expected)
    {
        /** @var RecipientEntity $recipientEntity */
        $recipientEntity = new RecipientEntity(
            'class',
            'id',
            $label,
            $organization
        );

        $recipient = new Recipient($email, $name, $recipientEntity);
        $this->assertEquals($expected, $recipient->getLabel());
    }

    public static function recipientLabelProvider()
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
