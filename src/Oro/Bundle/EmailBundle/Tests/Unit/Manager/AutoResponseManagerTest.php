<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Symfony\Component\Yaml\Yaml;

class AutoResponseManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Registry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $emailBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $emailProcessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $render;

    /** @var AutoResponseManager */
    protected $autoResponseManager;

    /** @var array|null */
    protected $definitions;

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

        $this->logger = $this->createMock('Psr\Log\LoggerInterface');

        $this->render = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRenderer')
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }));

        $this->autoResponseManager = new AutoResponseManager(
            $this->registry,
            $this->emailBuilder,
            $this->emailProcessor,
            $this->render,
            $this->logger,
            $translator,
            'en'
        );
    }

    /**
     * @dataProvider definitionNamesProvider
     */
    public function testCreateRuleExpr($definition)
    {
        $expr = $this->autoResponseManager->createRuleExpr($this->getAutoResponseRule($definition), new Email());
        $this->assertEquals($this->getExpectedExpression($definition), $expr);
    }

    public function definitionNamesProvider()
    {
        return [
            ['and'],
            ['combined'],
        ];
    }

    /**
     * @dataProvider applicableEmailsProvider
     */
    public function testGetApplicableRulesReturnsTheRule($definition, Email $email)
    {
        $mailbox = new Mailbox();
        $mailbox->setAutoResponseRules(new ArrayCollection([$this->getAutoResponseRule($definition)]));

        $rules = $this->autoResponseManager->getApplicableRules($mailbox, $email);
        $this->assertEquals(1, $rules->count());
    }

    public function applicableEmailsProvider()
    {
        return $this->emailsProvider('applicable_emails');
    }

    /**
     * @dataProvider inapplicableEmailsProvider
     */
    public function testGetApplicableRulesDoesNotReturnTheRule($definition, Email $email)
    {
        $mailbox = new Mailbox();
        $mailbox->setAutoResponseRules(new ArrayCollection([$this->getAutoResponseRule($definition)]));

        $rules = $this->autoResponseManager->getApplicableRules($mailbox, $email);
        $this->assertEquals(0, $rules->count());
    }

    public function inapplicableEmailsProvider()
    {
        return $this->emailsProvider('inapplicable_emails');
    }

    /**
     * @param string $definition
     *
     * @return AutoResponseRule
     */
    protected function getAutoResponseRule($definition = 'and')
    {
        $autoResponseRule = new AutoResponseRule();
        $createdAt = new DateTime('now', new DateTimeZone('UTC'));
        $createdAt->sub(DateInterval::createFromDateString('1 day'));
        $autoResponseRule->setCreatedAt($createdAt);
        $autoResponseRule->setDefinition($this->getDefinition($definition));

        return $autoResponseRule;
    }

    public function testCreateEmailEntity()
    {
        $expected = [
            'name' => 'email',
            'label' => 'oro.email.entity_label',
            'fields' => [
                [
                    'label' => 'oro.email.subject.label',
                    'name' => 'subject',
                    'type' => 'text',
                ],
                [
                    'label' => 'oro.email.email_body.label',
                    'name' => 'emailBody.bodyContent',
                    'type' => 'text',
                ],
                [
                    'label' => 'From',
                    'name' => 'fromName',
                    'type' => 'text',
                ],
                [
                    'label' => 'Cc',
                    'name' => 'cc.__index__.name',
                    'type' => 'text',
                ],
                [
                    'label' => 'Bcc',
                    'name' => 'bcc.__index__.name',
                    'type' => 'text',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->autoResponseManager->createEmailEntity());
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getDefinition($name)
    {
        return json_encode($this->getDefinitions()[$name]['definition']);
    }

    /**
     * @param string $definition
     *
     * @return array
     */
    protected function getExpectedExpression($definition)
    {
        return $this->getDefinitions()[$definition]['expression'];
    }

    private function emailsProvider($key)
    {
        $definitionNames = array_keys($this->getDefinitions());

        $results = [];
        foreach ($definitionNames as $definition) {
            $results[] = array_map(function ($data) use ($definition) {
                $body = new EmailBody();
                $body->setBodyContent($data['body']);

                $email = new Email();
                $email->setSubject($data['subject']);
                $email->setEmailBody($body);
                $email->setSentAt(new DateTime($data['date'], new DateTimeZone('UTC')));

                return [$definition, $email];
            }, $this->getDefinitions()[$definition][$key]);
        }

        return call_user_func_array('array_merge', $results);
    }

    /**
     * @return array
     */
    protected function getDefinitions()
    {
        if (!$this->definitions) {
            $this->definitions = Yaml::parse(file_get_contents($this->getAutoResponseRuleDefinitionsPath()));
        }

        return $this->definitions;
    }

    /**
     * @return string
     */
    protected function getAutoResponseRuleDefinitionsPath()
    {
        return __DIR__ . '/../Fixtures/autorResponseRuleDefinitions.yml';
    }
}
