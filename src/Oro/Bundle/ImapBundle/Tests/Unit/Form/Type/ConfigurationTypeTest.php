<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Manager\ImapSettingsChecker;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestUserEmailOrigin;
use Oro\Bundle\ImapBundle\Validator\Constraints\EmailFolders;
use Oro\Bundle\ImapBundle\Validator\Constraints\EmailFoldersValidator;
use Oro\Bundle\ImapBundle\Validator\Constraints\ImapConnectionConfigurationValidator;
use Oro\Bundle\ImapBundle\Validator\Constraints\SmtpConnectionConfigurationValidator;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Validator\Constraints\ValidValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigurationTypeTest extends FormIntegrationTestCase
{
    private const TEST_PASSWORD = 'somePassword';
    private const OAUTH_ACCOUNT_TYPE = 'oauth1';

    /** @var SymmetricCrypterInterface */
    private $encryptor;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ImapSettingsChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $imapSettingsChecker;

    /** @var SmtpSettingsChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $smtpSettingsChecker;

    protected function setUp(): void
    {
        $this->encryptor = new DefaultCrypter('someKey');
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->imapSettingsChecker = $this->createMock(ImapSettingsChecker::class);
        $this->smtpSettingsChecker = $this->createMock(SmtpSettingsChecker::class);

        $organization = $this->createMock(Organization::class);
        $user = $this->createMock(User::class);
        $user->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);
        $this->tokenAccessor->expects(self::any())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        new CheckButtonType(),
                        new EmailFolderTreeType(),
                        new ConfigurationType($this->encryptor, $this->tokenAccessor, $this->translator)
                    ],
                    [
                        FormType::class => [new TooltipFormExtensionStub($this)]
                    ]
                ),
                $this->getValidatorExtension(true)
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators(): array
    {
        $this->imapSettingsChecker->expects(self::any())
            ->method('checkConnection')
            ->willReturn(true);
        $smtpSettingsFactory = $this->createMock(SmtpSettingsFactory::class);
        $smtpSettingsFactory->expects(self::any())
            ->method('createFromUserEmailOrigin')
            ->willReturn(new SmtpSettings());
        $this->smtpSettingsChecker->expects(self::any())
            ->method('checkConnection')
            ->willReturn(true);

        return [
            ValidValidator::class => $this->createMock(ConstraintValidatorInterface::class),
            EmailFoldersValidator::class => new EmailFoldersValidator(),
            ImapConnectionConfigurationValidator::class => new ImapConnectionConfigurationValidator(
                $this->imapSettingsChecker
            ),
            SmtpConnectionConfigurationValidator::class => new SmtpConnectionConfigurationValidator(
                $this->smtpSettingsChecker,
                $smtpSettingsFactory
            )
        ];
    }

    public function testBindValidDataShouldBindCorrectDataExceptPassword(): void
    {
        $formData =
        [
            'imapHost' => 'someHost',
            'imapPort' => '123',
            'smtpHost' => '',
            'smtpPort' => '',
            'imapEncryption' => 'ssl',
            'smtpEncryption' => 'ssl',
            'user' => 'someUser',
            'accountType' => self::OAUTH_ACCOUNT_TYPE,
            'password' => self::TEST_PASSWORD,
        ];
        $form = $this->factory->create(ConfigurationType::class);
        $form->submit($formData);

        self::assertEquals('someHost', $form->get('imapHost')->getData());
        self::assertEquals(self::OAUTH_ACCOUNT_TYPE, $form->get('accountType')->getData());
        self::assertEquals('123', $form->get('imapPort')->getData());
        self::assertEquals('', $form->get('smtpHost')->getData());
        self::assertEquals('', $form->get('smtpPort')->getData());
        self::assertEquals('ssl', $form->get('imapEncryption')->getData());
        self::assertEquals('ssl', $form->get('smtpEncryption')->getData());
        self::assertEquals('someUser', $form->get('user')->getData());

        /** @var UserEmailOrigin $entity */
        $entity = $form->getData();
        self::assertInstanceOf(UserEmailOrigin::class, $entity);

        self::assertEquals('someHost', $entity->getImapHost());
        self::assertEquals(self::OAUTH_ACCOUNT_TYPE, $entity->getAccountType());
        self::assertEquals('123', $entity->getImapPort());
        self::assertEquals('', $entity->getSmtpHost());
        self::assertEquals('', $entity->getSmtpPort());
        self::assertEquals('ssl', $entity->getImapEncryption());
        self::assertEquals('ssl', $entity->getSmtpEncryption());
        self::assertEquals('someUser', $entity->getUser());

        self::assertEquals(self::TEST_PASSWORD, $this->encryptor->decryptData($entity->getPassword()));
    }

    public function testBindValidDataShouldNotCreateEmptyEntity(): void
    {
        $form = $this->factory->create(ConfigurationType::class);

        $form->submit([
            'imapHost' => '',
            'imapPort' => '',
            'smtpHost' => '',
            'smtpPort' => '',
            'imapEncryption' => '',
            'smtpEncryption' => '',
            'user' => '',
            'password' => ''
        ]);

        self::assertNull($form->getData());
    }

    /**
     * @dataProvider setFolderDataProvider
     */
    public function testBindValidFolderData(
        string|array|null $foldersForm,
        ?array $folders,
        int $expectedFoldersCount
    ): void {
        $formData = [
            'imapHost' => 'someHost',
            'imapPort' => '123',
            'smtpHost' => '',
            'smtpPort' => '',
            'accountType' => self::OAUTH_ACCOUNT_TYPE,
            'imapEncryption' => 'ssl',
            'smtpEncryption' => 'ssl',
            'user' => 'someUser',
            'password' => '',
            'folders' => $foldersForm
        ];
        $form = $this->factory->create(ConfigurationType::class);

        $entity = new TestUserEmailOrigin(1);
        $rootFolder = new EmailFolder();
        $rootFolder->setFullName('Root');
        $entity->addFolder($rootFolder);
        if (is_array($folders)) {
            foreach ($folders as $folderName) {
                $folder = new EmailFolder();
                $folder->setFullName($folderName);
                $folder->setParentFolder($rootFolder);
                $rootFolder->addSubFolder($folder);
                $entity->addFolder($folder);
            }
        }

        $form->setData($entity);
        $form->submit($formData);
        $entity = $form->getData();
        self::assertEquals($expectedFoldersCount, $entity->getFolders()->count());
    }

    public function setFolderDataProvider(): array
    {
        return [
            'one folder' => [
                '[{"fullName":"Test1","name":"Test1","type":"other","subFolders":[]}]',
                ['Test1'],
                2
            ],
            'two folders' => [
                '[{"fullName":"Test3","name":"Test3","type":"other","subFolders":[]},'
                . '{"fullName":"Test2","name":"Test2","type":"other","subFolders":[]}]',
                ['Test3', 'Test2'],
                3
            ],
            'no folders' => [
                [],
                [],
                1
            ],
            'no folders data' => [
                null,
                null,
                1
            ],
        ];
    }

    /**
     * If submitted empty password, it should be populated from old entity
     */
    public function testBindEmptyPassword(): void
    {
        $form = $this->factory->create(ConfigurationType::class);

        $entity = new UserEmailOrigin();
        $entity->setPassword(self::TEST_PASSWORD);

        $form->setData($entity);
        $form->submit(
            [
                'imapHost' => 'someHost',
                'imapPort' => '123',
                'smtpHost' => '',
                'smtpPort' => '',
                'accountType' => self::OAUTH_ACCOUNT_TYPE,
                'imapEncryption' => 'ssl',
                'smtpEncryption' => 'ssl',
                'user' => 'someUser',
                'password' => ''
            ]
        );

        self::assertEquals(self::TEST_PASSWORD, $entity->getPassword());
    }

    /**
     * In case when user or host field was changed new configuration should be created
     * and old one will be not active.
     */
    public function testCreatingNewConfiguration(): void
    {
        $form = $this->factory->create(ConfigurationType::class);

        $entity = new UserEmailOrigin();
        $entity->setImapHost('someHost');
        self::assertTrue($entity->isActive());

        $form->setData($entity);
        $form->submit(
            [
                'useImap' => 1,
                'imapHost' => 'someHost',
                'imapPort' => '123',
                'smtpHost' => '',
                'smtpPort' => '',
                'accountType' => self::OAUTH_ACCOUNT_TYPE,
                'imapEncryption' => 'ssl',
                'smtpEncryption' => 'ssl',
                'user' => 'someUser',
                'password' => 'somPassword'
            ]
        );

        self::assertNotSame($entity, $form->getData());

        self::assertInstanceOf(UserEmailOrigin::class, $form->getData());
        self::assertTrue($form->getData()->isActive());
    }

    /**
     * In case when user or host field was changed new configuration should NOT be created if imap and smtp
     * are inactive.
     */
    public function testNotCreatingNewConfigurationWhenImapInactive(): void
    {
        $form = $this->factory->create(ConfigurationType::class);

        $entity = new UserEmailOrigin();
        $entity->setImapHost('someHost');
        self::assertTrue($entity->isActive());

        $form->setData($entity);
        $form->submit(
            [
                'useImap' => 0,
                'useSmtp' => 0,
                'imapHost' => 'someHost',
                'accountType' => self::OAUTH_ACCOUNT_TYPE,
                'imapPort' => '123',
                'smtpHost' => '',
                'smtpPort' => '',
                'imapEncryption' => 'ssl',
                'smtpEncryption' => 'ssl',
                'user' => 'someUser',
                'password' => 'somPassword'
            ]
        );

        self::assertNotSame($entity, $form->getData());
        self::assertNull($form->getData());
    }

    /**
     * Case when user submit empty form but have configuration.
     * Configuration should be not active and relation should be broken
     */
    public function testSubmitEmptyForm(): void
    {
        $form = $this->factory->create(ConfigurationType::class);

        $entity = new UserEmailOrigin();
        self::assertTrue($entity->isActive());

        $form->setData($entity);
        $form->submit(
            [
                'imapHost' => '',
                'imapPort' => '',
                'smtpHost' => '',
                'type'     => '',
                'smtpPort' => '',
                'imapEncryption' => '',
                'smtpEncryption' => '',
                'user' => '',
                'password' => ''
            ]
        );

        self::assertNotSame($entity, $form->getData());
        self::assertNotInstanceOf(UserEmailOrigin::class, $form->getData());
        self::assertNull($form->getData());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submitData, bool $expectedValid): void
    {
        $form = $this->factory->create(ConfigurationType::class);
        $form->submit($submitData);

        self::assertEquals($expectedValid, $form->isValid());
        self::assertTrue($form->isSynchronized());

        if (!$expectedValid) {
            $constraint = new EmailFolders();

            self::assertStringContainsString($constraint->message, (string)$form->getErrors());
        }

        self::assertInstanceOf(UserEmailOrigin::class, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $config = [
            'imapHost' => 'someImapHost',
            'imapPort' => '123',
            'smtpHost' => 'someSmtpHost',
            'smtpPort' => '456',
            'accountType' => self::OAUTH_ACCOUNT_TYPE,
            'imapEncryption' => 'ssl',
            'smtpEncryption' => 'ssl',
            'user' => 'someUser',
            'password' => 'somPassword',
            'folders' => '[]'
        ];

        return [
            'use imap disabled, use smtp disabled' => [
                'submitData' => array_merge($config, ['useImap' => false, 'useSmtp' => false]),
                'expectedValid' => true,
            ],
            'use imap disabled, use smtp enabled' => [
                'submitData' => array_merge($config, ['useImap' => false, 'useSmtp' => true]),
                'expectedValid' => true,
            ],
            'use imap enabled, use smtp enabled' => [
                'submitData' => array_merge($config, ['useImap' => true, 'useSmtp' => true]),
                'expectedValid' => false,
            ],
            'use imap enabled, use smtp disabled' => [
                'submitData' => array_merge($config, ['useImap' => true, 'useSmtp' => false]),
                'expectedValid' => false,
            ]
        ];
    }

    public function testGetName(): void
    {
        $type = new ConfigurationType(
            $this->encryptor,
            $this->tokenAccessor,
            $this->translator
        );
        self::assertEquals(ConfigurationType::NAME, $type->getName());
    }
}
