<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\SearchBundle\Query\Result;

class EmailRecipientsHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $aclHelper;
    protected $dqlNameFormatter;
    protected $nameFormatter;
    protected $configManager;
    protected $translator;
    protected $emailOwnerProvider;
    protected $registry;
    protected $addressHelper;

    protected $emailRecipientsHelper;
    protected $indexer;

    public function setUp()
    {
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dqlNameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailOwnerProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressHelper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Tools\EmailAddressHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexer = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Indexer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailRecipientsHelper = new EmailRecipientsHelper(
            $this->aclHelper,
            $this->dqlNameFormatter,
            $this->nameFormatter,
            $this->configManager,
            $this->translator,
            $this->emailOwnerProvider,
            $this->registry,
            $this->addressHelper,
            $this->indexer
        );
    }

    /**
     * @dataProvider getRecipientsDataProvider
     */
    public function testGetRecipients(EmailRecipientsProviderArgs $args, array $resultEmails)
    {
        $this->dqlNameFormatter->expects($this->once())
            ->method('getFormattedNameDQL')
            ->with('u', 'Oro\Bundle\UserBundle\Entity\User')
            ->will($this->returnValue('u.name'));

        $expressionBuiled = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->disableOriginalConstructor()
            ->setMethods(['in'])
            ->getMock();

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('andWhere')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('expr')
            ->will($this->returnValue($expressionBuiled));

        $userRepository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->expects($this->once())
            ->method('getPrimaryEmailsQb')
            ->will($this->returnValue($qb));

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractclass();
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($resultEmails));

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->will($this->returnValue($query));

        $searchQueryMock = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Query')
            ->disableOriginalConstructor()
            ->getMock();

        $searchResultMock = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result\Item')
            ->disableOriginalConstructor()
            ->getMock();

        $stubResult = new Result($searchQueryMock, [$searchResultMock]);

        $this->indexer->expects($this->once())
             ->method('simpleSearch')
             ->will($this->returnValue($stubResult));

        $this->emailRecipientsHelper->getRecipients($args, $userRepository, 'u', 'Oro\Bundle\UserBundle\Entity\User');
    }

    public function getRecipientsDataProvider()
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, null, 1),
                [
                    [
                        'name'  => 'Recipient <recipient@example.com>',
                        'email' => 'recipient@example.com',
                        'entityId'     => 1,
                        'organization' => 'org',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider filterRecipientsDataProvider
     */
    public function testFilterRecipients(EmailRecipientsProviderArgs $args, array $recipients, array $expectedResult)
    {
        $this->assertEquals($expectedResult, EmailRecipientsHelper::filterRecipients($args, $recipients));
    }

    public function filterRecipientsDataProvider()
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, 're', 100),
                [
                    new Recipient('recipient@example.com', 'Recipient <recipient@example.com>'),
                ],
                [
                    new Recipient('recipient@example.com', 'Recipient <recipient@example.com>'),
                ],
            ],
            [
                new EmailRecipientsProviderArgs(null, 'res', 100),
                [
                    new Recipient('recipient@example.com', 'Recipient <recipient@example.com>'),
                ],
                [],
            ],
            [
                new EmailRecipientsProviderArgs(null, 're', 100, [new Recipient('recipient@example.com', 'name')]),
                [
                    new Recipient('recipient2@example.com', 'Recipient2 <recipient2@example.com>'),
                    new Recipient('recipient@example.com', 'Recipient <recipient@example.com>'),
                ],
                [
                    new Recipient('recipient2@example.com', 'Recipient2 <recipient2@example.com>'),
                ],
            ],
        ];
    }
}
