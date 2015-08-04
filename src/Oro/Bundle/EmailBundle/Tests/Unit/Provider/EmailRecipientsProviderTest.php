<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProvider;

class EmailRecipientsProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $emailRecipientsProvider;

    public function setUp()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }));

        $this->emailRecipientsProvider = new EmailRecipientsProvider($translator);
    }

    public function testGetEmailRecipientsShouldReturnEmptyArrayIfThereAreNoProviders()
    {
        $this->assertEmpty($this->emailRecipientsProvider->getEmailRecipients());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetEmailRecipients(array $providers, array $expectedRecipients, $limit = 100)
    {
        $this->emailRecipientsProvider->setProviders($providers);

        $actualRecipients = $this->emailRecipientsProvider->getEmailRecipients(null, null, $limit);
        $this->assertEquals($expectedRecipients, $actualRecipients);
    }

    public function dataProvider()
    {
        return [
            [
                [
                    $this->createProvider('section1', [
                        'recipient@example.com'  => 'Recipient <recipient@example.com>',
                        'recipient2@example.com' => 'Recipient2 <recipient2@example.com>',
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
                        'recipient@example.com'  => 'Recipient <recipient@example.com>',
                        'recipient2@example.com' => 'Recipient2 <recipient2@example.com>',
                    ]),
                    $this->createProvider('section2', [
                        'recipient3@example.com'  => 'Recipient3 <recipient3@example.com>',
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
                        'recipient@example.com'  => 'Recipient <recipient@example.com>',
                        'recipient2@example.com' => 'Recipient2 <recipient2@example.com>',
                    ]),
                    $this->createProvider('section2', [
                        'recipient3@example.com'  => 'Recipient3 <recipient3@example.com>',
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
                ],
                2,
            ],
        ];
    }

    protected function createProvider($section, array $provided)
    {
        $provider = $this->getMock('Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface');
        $provider->expects($this->any())
            ->method('getSection')
            ->will($this->returnValue($section));
        $provider->expects($this->once())
            ->method('getRecipients')
            ->will($this->returnValue($provided));

        return $provider;
    }
}
