<?php

namespace Oro\src\Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRuleCondition;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;

class AutoResponseManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $registry;
    protected $emailBuilder;
    protected $emailProcessor;
    protected $logger;

    /** @var AutoResponseManager */
    protected $autoResponseManager;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailBuilder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailModelBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailProcessor = $this->getMockBuilder('Oro\Bundle\EmailBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMock('Psr\Log\LoggerInterface');

        $this->autoResponseManager = new AutoResponseManager(
            $this->registry,
            $this->emailBuilder,
            $this->emailProcessor,
            $this->logger,
            'en'
        );
    }

    public function testCreateRuleExpr()
    {
        $expectedExpr = [
            '@and' => [
                ['@empty' => ['$subject']],
                ['@contains' => ['$emailBody.bodyContent', 'offer']],
                ['@contains' => ['$emailBody.bodyContent', 'sale']],
                ['@contains' => ['$emailBody.bodyContent', 'won']],
            ],
        ];

        $expr = $this->autoResponseManager->createRuleExpr($this->getAutoResponseRule(), new Email());
        $this->assertEquals($expectedExpr, $expr);
    }

    /**
     * @dataProvider applicableEmailsProvider
     */
    public function testGetApplicableRulesReturnsTheRule(Email $email)
    {
        $mailbox = new Mailbox();
        $mailbox->setAutoResponseRules(new ArrayCollection([$this->getAutoResponseRule()]));

        $rules = $this->autoResponseManager->getApplicableRules($mailbox, $email);
        $this->assertEquals(1, $rules->count());
    }

    /**
     * @dataProvider inapplicableEmailsProvider
     */
    public function testGetApplicableRulesDoesNotReturnTheRule(Email $email)
    {
        $mailbox = new Mailbox();
        $mailbox->setAutoResponseRules(new ArrayCollection([$this->getAutoResponseRule()]));

        $rules = $this->autoResponseManager->getApplicableRules($mailbox, $email);
        $this->assertEquals(0, $rules->count());
    }

    public function applicableEmailsProvider()
    {
        $applicableBodies = [
            'This is email body with offer, sale and won words.',
            'This is email body with offer and won words.',
            'This is email body with sale and won words.',
        ];

        $data = array_map(function ($bodyContent) {
            $body = new EmailBody();
            $body->setBodyContent($bodyContent);

            $email = new Email();
            $email->setEmailBody($body);

            return [$email];
        }, $applicableBodies);

        return $data;
    }

    public function inapplicableEmailsProvider()
    {
        $applicableSubjectsAndBodies = [
            ['not empty subject', 'This is email body with offer, sale and won words.'],
            [null, 'This email has nothing.']
        ];

        $data = array_map(function ($subjectAndBody) {
            list($subject, $bodyContent) = $subjectAndBody;

            $body = new EmailBody();
            $body->setBodyContent($bodyContent);

            $email = new Email();
            $email->setSubject($subject);
            $email->setEmailBody($body);

            return [$email];
        }, $applicableSubjectsAndBodies);

        return $data;
    }

    protected function getAutoResponseRule()
    {
        $subjectCondition = new AutoResponseRuleCondition();
        $subjectCondition
            ->setField('subject')
            ->setFilterType(FilterUtility::TYPE_EMPTY);

        $offerCondition = new AutoResponseRuleCondition();
        $offerCondition
            ->setField('emailBody.bodyContent')
            ->setFilterType(TextFilterType::TYPE_CONTAINS)
            ->setFilterValue('offer');
        
        $saleCondition = new AutoResponseRuleCondition();
        $saleCondition
            ->setField('emailBody.bodyContent')
            ->setFilterType(TextFilterType::TYPE_CONTAINS)
            ->setFilterValue('sale');

        $wonCondition = new AutoResponseRuleCondition();
        $wonCondition
            ->setField('emailBody.bodyContent')
            ->setFilterType(TextFilterType::TYPE_CONTAINS)
            ->setFilterValue('won');

        $autoResponseRule = new AutoResponseRule();
        $autoResponseRule->addConditions([
            $subjectCondition,
            $offerCondition,
            $saleCondition,
            $wonCondition,
        ]);

        return $autoResponseRule;
    }
}
