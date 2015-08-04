<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;

class EmailRecipientsHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testFilterRecipients(EmailRecipientsProviderArgs $args, array $recipients, array $expectedResult)
    {
        $this->assertEquals($expectedResult, EmailRecipientsHelper::filterRecipients($args, $recipients));
    }

    public function dataProvider()
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, 're', 100),
                [
                    'recipient@example.com' => 'Recipient <recipient@example.com>',
                ],
                [
                    'recipient@example.com' => 'Recipient <recipient@example.com>',
                ],
            ],
            [
                new EmailRecipientsProviderArgs(null, 'res', 100),
                [
                    'recipient@example.com' => 'Recipient <recipient@example.com>',
                ],
                [],
            ],
            [
                new EmailRecipientsProviderArgs(null, 're', 100, ['recipient@example.com']),
                [
                    'recipient@example.com' => 'Recipient <recipient@example.com>',
                    'recipient2@example.com' => 'Recipient2 <recipient2@example.com>',
                ],
                [
                    'recipient2@example.com' => 'Recipient2 <recipient2@example.com>',
                ],
            ],
        ];
    }
}
