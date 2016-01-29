<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;

class MailboxTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $user;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $origin;

    protected function setUp()
    {
        $this->user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\user')
            ->disableOriginalConstructor()
            ->getMock();

        $this->origin = $this->getMockBuilder('Oro\Bundle\ImapBundle\Entity\UserEmailOrigin')
            ->disableOriginalConstructor()
            ->getMock();

        $this->origin->expects($this->any())->method('getUser')->willReturn($this->user);
    }

    /**
     * @param bool $skipOrigin
     * @param string $accessToken
     * @param string $accountType
     *
     * @dataProvider setDataProviderAccountType
     */
    public function testGetAccountType($skipOrigin, $accessToken, $accountType)
    {
        $mailbox = new Mailbox();

        $mailbox->setOrigin($this->origin);
        if ($skipOrigin) {
            $mailbox->setOrigin(null);
            $this->assertEmpty($mailbox->getImapAccountType());
        } else {
            $this->origin->expects($this->any())->method('getAccessToken')->willReturn($accessToken);
            $this->assertEquals($accountType, $mailbox->getImapAccountType()->getAccountType());
        }
    }

    /**
     * @return array
     */
    public function setDataProviderAccountType()
    {
        return [
            'empty origin' => [
                'skipOrigin' => true,
                'accessToken' => '',
                'accountType' => ''
            ],
            'expect Gmail account type' => [
                'skipOrigin' => false,
                'accessToken' => '12345',
                'accountType' => AccountTypeModel::ACCOUNT_TYPE_GMAIL
            ],
            'expect Other account type' => [
                'skipOrigin' => false,
                'accessToken' => '',
                'accountType' => AccountTypeModel::ACCOUNT_TYPE_OTHER
            ]
        ];
    }
}
