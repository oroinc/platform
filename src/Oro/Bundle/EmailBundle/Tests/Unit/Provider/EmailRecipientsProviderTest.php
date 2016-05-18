<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProvider;
use Oro\Bundle\EmailBundle\Model\Recipient;

class EmailRecipientsProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $emailRecipientsHelper;

    protected $emailRecipientsProvider;

    public function setUp()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }));

        $this->emailRecipientsHelper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailRecipientsHelper->expects($this->any())
            ->method('createRecipientData')
            ->will($this->returnCallback(function (Recipient $recipient) {
                return [
                    'id'   => $recipient->getName(),
                    'text' => $recipient->getName(),
                ];
            }));

        $this->emailRecipientsProvider = new EmailRecipientsProvider($translator, $this->emailRecipientsHelper);
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

        $actualRecipients = $this->emailRecipientsProvider->getEmailRecipients(null, null, null, $limit);
        $this->assertEquals($expectedRecipients, $actualRecipients);
    }

    public function dataProvider()
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

    /**
     * @param string    $section
     * @param array     $provided
     * @param int       $recipientExactly
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProvider($section, array $provided, $recipientExactly = 1)
    {
        $provider = $this->getMock('Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface');
        $provider->expects($this->any())
            ->method('getSection')
            ->will($this->returnValue($section));
        $provider->expects($this->exactly($recipientExactly))
            ->method('getRecipients')
            ->will($this->returnValue($provided));

        return $provider;
    }
}
