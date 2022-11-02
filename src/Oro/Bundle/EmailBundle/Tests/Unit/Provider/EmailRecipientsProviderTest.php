<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProvider;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailRecipientsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailRecipientsProvider */
    private $emailRecipientsProvider;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id;
            });

        $emailRecipientsHelper = $this->createMock(EmailRecipientsHelper::class);
        $emailRecipientsHelper->expects($this->any())
            ->method('createRecipientData')
            ->willReturnCallback(function (Recipient $recipient) {
                return [
                    'id'   => $recipient->getName(),
                    'text' => $recipient->getName(),
                ];
            });

        $this->emailRecipientsProvider = new EmailRecipientsProvider($translator, $emailRecipientsHelper);
    }

    public function testGetEmailRecipientsShouldReturnEmptyArrayIfThereAreNoProviders()
    {
        $this->assertEmpty($this->emailRecipientsProvider->getEmailRecipients());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetEmailRecipients(array $providers, array $expectedRecipients, int $limit = 100)
    {
        $this->emailRecipientsProvider->setProviders($providers);

        $actualRecipients = $this->emailRecipientsProvider->getEmailRecipients(null, null, null, $limit);
        $this->assertEquals($expectedRecipients, $actualRecipients);
    }

    public function dataProvider(): array
    {
        return [
            [
                [
                    $this->createProvider('section1', [
                        new Recipient('recipient@example.com', 'Recipient <recipient@example.com>'),
                        new Recipient('recipient2@example.com', 'Recipient2 <recipient2@example.com>'),
                    ]),
                ],
                [
                    [
                        'text' => 'section1',
                        'children' => [
                            [
                                'id'   => 'Recipient <recipient@example.com>',
                                'text' => 'Recipient <recipient@example.com>',
                            ],
                            [
                                'id'   => 'Recipient2 <recipient2@example.com>',
                                'text' => 'Recipient2 <recipient2@example.com>',
                            ],
                        ],
                    ]
                ],
            ],
            [
                [
                    $this->createProvider('section1', [
                        new Recipient('recipient@example.com', 'Recipient <recipient@example.com>'),
                        new Recipient('recipient2@example.com', 'Recipient2 <recipient2@example.com>'),
                    ]),
                    $this->createProvider('section2', [
                        new Recipient('recipient3@example.com', 'Recipient3 <recipient3@example.com>'),
                    ]),
                ],
                [
                    [
                        'text' => 'section1',
                        'children' => [
                            [
                                'id'   => 'Recipient <recipient@example.com>',
                                'text' => 'Recipient <recipient@example.com>',
                            ],
                            [
                                'id'   => 'Recipient2 <recipient2@example.com>',
                                'text' => 'Recipient2 <recipient2@example.com>',
                            ],
                        ],
                    ],
                    [
                        'text' => 'section2',
                        'children' => [
                            [
                                'id'   => 'Recipient3 <recipient3@example.com>',
                                'text' => 'Recipient3 <recipient3@example.com>',
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    $this->createProvider('section1', [
                        new Recipient('recipient@example.com', 'Recipient <recipient@example.com>'),
                        new Recipient('recipient2@example.com', 'Recipient2 <recipient2@example.com>'),
                    ]),
                    $this->createProvider('section2', [
                        new Recipient('recipient3@example.com', 'Recipient3 <recipient3@example.com>'),
                    ], 0),
                ],
                [
                    [
                        'text' => 'section1',
                        'children' => [
                            [
                                'id'   => 'Recipient <recipient@example.com>',
                                'text' => 'Recipient <recipient@example.com>',
                            ],
                            [
                                'id'   => 'Recipient2 <recipient2@example.com>',
                                'text' => 'Recipient2 <recipient2@example.com>',
                            ],
                        ],
                    ],
                ],
                2,
            ],
        ];
    }

    private function createProvider(
        string $section,
        array $provided,
        int $recipientExactly = 1
    ): EmailRecipientsProviderInterface {
        $provider = $this->createMock(EmailRecipientsProviderInterface::class);
        $provider->expects($this->any())
            ->method('getSection')
            ->willReturn($section);
        $provider->expects($this->exactly($recipientExactly))
            ->method('getRecipients')
            ->willReturn($provided);

        return $provider;
    }
}
