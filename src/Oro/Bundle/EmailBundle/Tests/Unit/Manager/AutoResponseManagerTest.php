<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

class AutoResponseManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EmailModelBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $emailBuilder;

    /** @var EmailModelSender|\PHPUnit\Framework\MockObject\MockObject */
    private $emailModelSender;

    /** @var EmailRenderer|\PHPUnit\Framework\MockObject\MockObject */
    private $emailRenderer;

    /** @var AutoResponseManager */
    private $autoResponseManager;

    private ?array $definitions = null;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->emailBuilder = $this->createMock(EmailModelBuilder::class);
        $this->emailModelSender = $this->createMock(EmailModelSender::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->emailRenderer = $this->createMock(EmailRenderer::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn (string $id) => $id . '_translated');

        $this->autoResponseManager = new AutoResponseManager(
            $this->registry,
            $this->emailBuilder,
            $this->emailModelSender,
            $this->emailRenderer,
            $logger,
            $translator,
            'en'
        );
    }

    /**
     * @dataProvider definitionNamesProvider
     */
    public function testCreateRuleExpr(string $definition): void
    {
        $expr = $this->autoResponseManager->createRuleExpr($this->getAutoResponseRule($definition), new Email());
        self::assertEquals($this->getExpectedExpression($definition), $expr);
    }

    public function definitionNamesProvider(): array
    {
        return [
            ['and'],
            ['combined'],
        ];
    }

    /**
     * @dataProvider applicableEmailsProvider
     */
    public function testGetApplicableRulesReturnsTheRule(string $definition, Email $email): void
    {
        $mailbox = new Mailbox();
        $mailbox->setAutoResponseRules(new ArrayCollection([$this->getAutoResponseRule($definition)]));

        $rules = $this->autoResponseManager->getApplicableRules($mailbox, $email);
        self::assertEquals(1, $rules->count());
    }

    public function applicableEmailsProvider(): array
    {
        return $this->emailsProvider('applicable_emails');
    }

    /**
     * @dataProvider inapplicableEmailsProvider
     */
    public function testGetApplicableRulesDoesNotReturnTheRule(string $definition, Email $email): void
    {
        $mailbox = new Mailbox();
        $mailbox->setAutoResponseRules(new ArrayCollection([$this->getAutoResponseRule($definition)]));

        $rules = $this->autoResponseManager->getApplicableRules($mailbox, $email);
        self::assertEquals(0, $rules->count());
    }

    public function inapplicableEmailsProvider(): array
    {
        return $this->emailsProvider('inapplicable_emails');
    }

    /**
     * @dataProvider applicableEmailsProvider
     */
    public function testSendAutoResponses(string $definition, Email $email): void
    {
        $mailbox = new Mailbox();
        $origin = new UserEmailOrigin();
        $origin->setUser(new User());
        $mailbox->setOrigin($origin);
        $mailbox->setAutoResponseRules(new ArrayCollection([$this->getAutoResponseRule($definition)]));

        $emailAddress = new EmailAddress();
        $emailAddress->setEmail('test@test.com');
        $email->setFromEmailAddress($emailAddress);

        $repo = $this->createMock(MailboxRepository::class);
        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(Mailbox::class)
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('findForEmail')
            ->with($email)
            ->willReturn([$mailbox]);

        $emailModel = new EmailModel();
        $this->emailBuilder->expects(self::once())
            ->method('createReplyEmailModel')
            ->with($email)
            ->willReturn($emailModel);

        $this->emailRenderer->expects(self::exactly(2))
            ->method('renderTemplate')
            ->willReturnMap(
                [
                    ['SUBJECT EN', ['entity' => $email], 'RENDERED EN SUBJECT'],
                    ['CONTENT EN', ['entity' => $email], 'RENDERED EN CONTENT'],
                ]
            );
        $this->emailModelSender->expects(self::once())
            ->method('send')
            ->with($emailModel, $origin);

        $this->autoResponseManager->sendAutoResponses($email);

        self::assertEquals('RENDERED EN SUBJECT', $emailModel->getSubject());
        self::assertEquals('RENDERED EN CONTENT', $emailModel->getBody());
    }

    private function getAutoResponseRule(string $definition = 'and'): AutoResponseRule
    {
        $autoResponseRule = new AutoResponseRule();
        $createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $createdAt->sub(\DateInterval::createFromDateString('1 day'));
        $autoResponseRule->setCreatedAt($createdAt);
        $autoResponseRule->setDefinition($this->getDefinition($definition));
        $autoResponseRule->setActive(true);

        $template = new EmailTemplate();
        $template->setContent('TEST');
        $template->setSubject('SUBJECT');
        $translation = new EmailTemplateTranslation();
        $translation->setSubject('SUBJECT EN');
        $translation->setContent('CONTENT EN');
        $localization = new Localization();
        $language = new Language();
        $language->setCode('en');
        $localization->setLanguage($language);
        $translation->setLocalization($localization);
        $template->addTranslation($translation);
        $autoResponseRule->setTemplate($template);

        return $autoResponseRule;
    }

    public function testCreateEmailEntity(): void
    {
        $expected = [
            'name' => 'email',
            'label' => 'oro.email.entity_label_translated',
            'fields' => [
                [
                    'label' => 'oro.email.subject.label_translated',
                    'name' => 'subject',
                    'type' => 'text',
                ],
                [
                    'label' => 'oro.email.email_body.label_translated',
                    'name' => 'emailBody.bodyContent',
                    'type' => 'text',
                ],
                [
                    'label' => 'From_translated',
                    'name' => 'fromName',
                    'type' => 'text',
                ],
                [
                    'label' => 'Cc_translated',
                    'name' => 'cc.__index__.name',
                    'type' => 'text',
                ],
                [
                    'label' => 'Bcc_translated',
                    'name' => 'bcc.__index__.name',
                    'type' => 'text',
                ],
            ],
        ];

        self::assertEquals($expected, $this->autoResponseManager->createEmailEntity());
    }

    private function getDefinition(string $name): string
    {
        return json_encode($this->getDefinitions()[$name]['definition'], JSON_THROW_ON_ERROR);
    }

    private function getExpectedExpression(string $definition): array
    {
        return $this->getDefinitions()[$definition]['expression'];
    }

    private function emailsProvider(string $key): array
    {
        $definitionNames = array_keys($this->getDefinitions());

        $results = [];
        foreach ($definitionNames as $definition) {
            $results[] = array_map(static function ($data) use ($definition) {
                $body = new EmailBody();
                $body->setBodyContent($data['body']);

                $email = new Email();
                $email->setSubject($data['subject']);
                $email->setEmailBody($body);
                $email->setSentAt(new \DateTime($data['date'], new \DateTimeZone('UTC')));

                return [$definition, $email];
            }, $this->getDefinitions()[$definition][$key]);
        }

        return array_merge(...$results);
    }

    private function getDefinitions(): array
    {
        if (!$this->definitions) {
            $this->definitions = Yaml::parse(file_get_contents($this->getAutoResponseRuleDefinitionsPath()));
        }

        return $this->definitions;
    }

    private function getAutoResponseRuleDefinitionsPath(): string
    {
        return __DIR__ . '/../Fixtures/autoResponseRuleDefinitions.yml';
    }
}
