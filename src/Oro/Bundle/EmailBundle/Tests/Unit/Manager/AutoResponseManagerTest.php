<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
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
    /** @var Registry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EmailModelBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $emailBuilder;

    /** @var Processor|\PHPUnit\Framework\MockObject\MockObject */
    private $emailProcessor;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var EmailRenderer|\PHPUnit\Framework\MockObject\MockObject */
    private $render;

    /** @var AutoResponseManager */
    private $autoResponseManager;

    /** @var array|null */
    private $definitions;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->emailBuilder = $this->createMock(EmailModelBuilder::class);
        $this->emailProcessor = $this->createMock(Processor::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->render = $this->createMock(EmailRenderer::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id;
            });

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
    public function testCreateRuleExpr(string $definition)
    {
        $expr = $this->autoResponseManager->createRuleExpr($this->getAutoResponseRule($definition), new Email());
        $this->assertEquals($this->getExpectedExpression($definition), $expr);
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
    public function testGetApplicableRulesReturnsTheRule(string $definition, Email $email)
    {
        $mailbox = new Mailbox();
        $mailbox->setAutoResponseRules(new ArrayCollection([$this->getAutoResponseRule($definition)]));

        $rules = $this->autoResponseManager->getApplicableRules($mailbox, $email);
        $this->assertEquals(1, $rules->count());
    }

    public function applicableEmailsProvider(): array
    {
        return $this->emailsProvider('applicable_emails');
    }

    /**
     * @dataProvider inapplicableEmailsProvider
     */
    public function testGetApplicableRulesDoesNotReturnTheRule(string $definition, Email $email)
    {
        $mailbox = new Mailbox();
        $mailbox->setAutoResponseRules(new ArrayCollection([$this->getAutoResponseRule($definition)]));

        $rules = $this->autoResponseManager->getApplicableRules($mailbox, $email);
        $this->assertEquals(0, $rules->count());
    }

    public function inapplicableEmailsProvider(): array
    {
        return $this->emailsProvider('inapplicable_emails');
    }

    /**
     * @dataProvider applicableEmailsProvider
     */
    public function testSendAutoResponses(string $definition, Email $email)
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
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Mailbox::class)
            ->willReturn($repo);

        $repo->expects($this->once())
            ->method('findForEmail')
            ->with($email)
            ->willReturn([$mailbox]);

        $emailModel = new EmailModel();
        $this->emailBuilder->expects($this->once())
            ->method('createReplyEmailModel')
            ->with($email)
            ->willReturn($emailModel);

        $this->render->expects($this->exactly(2))
            ->method('renderTemplate')
            ->willReturnMap([
                ['SUBJECT EN', ['entity' => $email], 'RENDERED EN SUBJECT'],
                ['CONTENT EN', ['entity' => $email], 'RENDERED EN CONTENT']
            ]);
        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($emailModel, $origin);

        $this->autoResponseManager->sendAutoResponses($email);

        $this->assertEquals('RENDERED EN SUBJECT', $emailModel->getSubject());
        $this->assertEquals('RENDERED EN CONTENT', $emailModel->getBody());
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
            $results[] = array_map(function ($data) use ($definition) {
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
        return __DIR__ . '/../Fixtures/autorResponseRuleDefinitions.yml';
    }
}
