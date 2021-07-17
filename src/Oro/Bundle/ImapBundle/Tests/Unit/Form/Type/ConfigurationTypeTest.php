<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\EmailBundle\Validator\Constraints\SmtpConnectionConfiguration;
use Oro\Bundle\EmailBundle\Validator\SmtpConnectionConfigurationValidator;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Manager\ImapSettingsChecker;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestUserEmailOrigin;
use Oro\Bundle\ImapBundle\Validator\Constraints\EmailFolders;
use Oro\Bundle\ImapBundle\Validator\Constraints\ImapConnectionConfiguration;
use Oro\Bundle\ImapBundle\Validator\EmailFoldersValidator;
use Oro\Bundle\ImapBundle\Validator\ImapConnectionConfigurationValidator;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\ConstraintValidatorInterface;

class ConfigurationTypeTest extends FormIntegrationTestCase
{
    private const TEST_PASSWORD = 'somePassword';

    private const OAUTH_ACCOUNT_TYPE = 'oauth1';

    /** @var SymmetricCrypterInterface */
    private $encryptor;

    /** @var TokenAccessorInterface|MockObject */
    private $tokenAccessor;

    /** @var Translator|MockObject */
    private $translator;

    /** @var ConfigProvider|MockObject */
    private $configProvider;

    /** @var ImapSettingsChecker|MockObject */
    private $imapSettingsChecker;

    /** @var SmtpSettingsChecker|MockObject */
    private $smtpSettingsChecker;

    protected function setUp(): void
    {
        $this->encryptor = new DefaultCrypter('someKey');

        $user = $this->createMock(User::class);

        $organization = $this->createMock(Organization::class);

        $user->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->translator = $this->createMock(Translator::class);

        $this->configProvider = $this
            ->getMockBuilder(ConfigProvider::class)
            ->setMethods(['hasConfig', 'getConfig', 'get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->imapSettingsChecker = $this->createMock(ImapSettingsChecker::class);
        $this->smtpSettingsChecker = $this->createMock(SmtpSettingsChecker::class);

        parent::setUp();
    }

    /**
     * @return array|\Symfony\Component\Form\FormExtensionInterface[]
     */
    protected function getExtensions()
    {
        $type = new ConfigurationType(
            $this->encryptor,
            $this->tokenAccessor,
            $this->translator
        );

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        CheckButtonType::class => new CheckButtonType(),
                        EmailFolderTreeType::class => new EmailFolderTreeType(),
                        ConfigurationType::class => $type
                    ],
                    [
                        FormType::class => [new TooltipFormExtension($this->configProvider, $this->translator)],
                    ]
                ),
                $this->getValidatorExtension(true)
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidators()
    {
        $valid = new Valid();
        $emailFolders = new EmailFolders();
        $imapConnectionConfiguration = new ImapConnectionConfiguration();
        $smtpConnectionConfiguration = new SmtpConnectionConfiguration();
        $this->imapSettingsChecker->expects($this->any())
            ->method('checkConnection')
            ->willReturn(true);
        /** @var SmtpSettingsFactory|MockObject $smtpSettingsFactory */
        $smtpSettingsFactory = $this->createMock(SmtpSettingsFactory::class);
        $smtpSettingsFactory->expects($this->any())
            ->method('create')
            ->willReturn(new SmtpSettings());
        $this->smtpSettingsChecker->expects($this->any())
            ->method('checkConnection')
            ->willReturn('');

        return [
            $valid->validatedBy() => $this->createMock(ConstraintValidatorInterface::class),
            $emailFolders->validatedBy() => new EmailFoldersValidator($this->translator),
            $imapConnectionConfiguration->validatedBy() => new ImapConnectionConfigurationValidator(
                $this->imapSettingsChecker
            ),
            $smtpConnectionConfiguration->validatedBy() => new SmtpConnectionConfigurationValidator(
                $this->smtpSettingsChecker,
                $smtpSettingsFactory
            )
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->encryptor);
    }

    public function testBindValidDataShouldBindCorrectDataExceptPassword()
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

        static::assertEquals('someHost', $form->get('imapHost')->getData());
        static::assertEquals(self::OAUTH_ACCOUNT_TYPE, $form->get('accountType')->getData());
        static::assertEquals('123', $form->get('imapPort')->getData());
        static::assertEquals('', $form->get('smtpHost')->getData());
        static::assertEquals('', $form->get('smtpPort')->getData());
        static::assertEquals('ssl', $form->get('imapEncryption')->getData());
        static::assertEquals('ssl', $form->get('smtpEncryption')->getData());
        static::assertEquals('someUser', $form->get('user')->getData());

        /** @var UserEmailOrigin $entity */
        $entity = $form->getData();
        static::assertInstanceOf(UserEmailOrigin::class, $entity);

        static::assertEquals('someHost', $entity->getImapHost());
        static::assertEquals(self::OAUTH_ACCOUNT_TYPE, $entity->getAccountType());
        static::assertEquals('123', $entity->getImapPort());
        static::assertEquals('', $entity->getSmtpHost());
        static::assertEquals('', $entity->getSmtpPort());
        static::assertEquals('ssl', $entity->getImapEncryption());
        static::assertEquals('ssl', $entity->getSmtpEncryption());
        static::assertEquals('someUser', $entity->getUser());

        static::assertEquals(self::TEST_PASSWORD, $this->encryptor->decryptData($entity->getPassword()));
    }

    public function testBindValidDataShouldNotCreateEmptyEntity()
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

        static::assertNull($form->getData());
    }

    /**
     * @param string $foldersForm
     * @param array $folders
     * @param array|bool $expectedFoldersCount
     *
     * @dataProvider setFolderDataProvider
     */
    public function testBindValidFolderData($foldersForm, $folders, $expectedFoldersCount)
    {
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
        $this->assertEquals($expectedFoldersCount, $entity->getFolders()->count());
    }

    /**
     * @return array
     */
    public function setFolderDataProvider()
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
    public function testBindEmptyPassword()
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

        $this->assertEquals(self::TEST_PASSWORD, $entity->getPassword());
    }

    /**
     * In case when user or host field was changed new configuration should be created
     * and old one will be not active.
     */
    public function testCreatingNewConfiguration()
    {
        $form = $this->factory->create(ConfigurationType::class);

        $entity = new UserEmailOrigin();
        $entity->setImapHost('someHost');
        $this->assertTrue($entity->isActive());

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

        $this->assertNotSame($entity, $form->getData());

        $this->assertInstanceOf(UserEmailOrigin::class, $form->getData());
        $this->assertTrue($form->getData()->isActive());
    }

    /**
     * In case when user or host field was changed new configuration should NOT be created if imap and smtp
     * are inactive.
     */
    public function testNotCreatingNewConfigurationWhenImapInactive()
    {
        $form = $this->factory->create(ConfigurationType::class);

        $entity = new UserEmailOrigin();
        $entity->setImapHost('someHost');
        $this->assertTrue($entity->isActive());

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

        $this->assertNotSame($entity, $form->getData());
        $this->assertNull($form->getData());
    }

    /**
     * Case when user submit empty form but have configuration
     * configuration should be not active and relation should be broken
     */
    public function testSubmitEmptyForm()
    {
        $form = $this->factory->create(ConfigurationType::class);

        $entity = new UserEmailOrigin();
        $this->assertTrue($entity->isActive());

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

        $this->assertNotSame($entity, $form->getData());
        $this->assertNotInstanceOf(UserEmailOrigin::class, $form->getData());
        $this->assertNull($form->getData());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submitData
     * @param bool $expectedValid
     */
    public function testSubmit(array $submitData, $expectedValid)
    {
        $form = $this->factory->create(ConfigurationType::class);
        $form->submit($submitData);

        $this->assertEquals($expectedValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());

        if (!$expectedValid) {
            $constraint = new EmailFolders();

            static::assertStringContainsString($constraint->message, (string)$form->getErrors());
        }

        $this->assertInstanceOf(UserEmailOrigin::class, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
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
            'use imap disabled, use stmp disabled' => [
                'submitData' => array_merge($config, ['useImap' => false, 'useSmtp' => false]),
                'expectedValid' => true,
            ],
            'use imap disabled, use stmp enabled' => [
                'submitData' => array_merge($config, ['useImap' => false, 'useSmtp' => true]),
                'expectedValid' => true,
            ],
            'use imap enabled, use stmp enabled' => [
                'submitData' => array_merge($config, ['useImap' => true, 'useSmtp' => true]),
                'expectedValid' => false,
            ],
            'use imap enabled, use stmp disabled' => [
                'submitData' => array_merge($config, ['useImap' => true, 'useSmtp' => false]),
                'expectedValid' => false,
            ]
        ];
    }

    public function testGetName()
    {
        $type = new ConfigurationType(
            $this->encryptor,
            $this->tokenAccessor,
            $this->translator
        );
        $this->assertEquals(ConfigurationType::NAME, $type->getName());
    }
}
