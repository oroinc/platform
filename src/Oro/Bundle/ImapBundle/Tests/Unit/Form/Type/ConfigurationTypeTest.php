<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestUserEmailOrigin;
use Oro\Bundle\ImapBundle\Validator\Constraints\EmailFolders;
use Oro\Bundle\ImapBundle\Validator\EmailFoldersValidator;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\ConstraintValidatorInterface;

class ConfigurationTypeTest extends FormIntegrationTestCase
{
    const TEST_PASSWORD = 'somePassword';

    /** @var SymmetricCrypterInterface */
    protected $encryptor;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    protected function setUp()
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

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $type = new ConfigurationType($this->encryptor, $this->tokenAccessor, $this->translator);

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

        return [
            $valid->validatedBy() => $this->createMock(ConstraintValidatorInterface::class),
            $emailFolders->validatedBy() => new EmailFoldersValidator($this->translator),
        ];
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->encryptor);
    }

    /**
     * @param array $formData
     * @param array|bool $expectedViewData
     *
     * @param array $expectedModelData
     *
     * @dataProvider setDataProvider
     */
    public function testBindValidData($formData, $expectedViewData, $expectedModelData)
    {
        $form = $this->factory->create(ConfigurationType::class);
        if ($expectedViewData) {
            $form->submit($formData);
            foreach ($expectedViewData as $name => $value) {
                $this->assertEquals($value, $form->get($name)->getData());
            }

            $entity = $form->getData();
            foreach ($expectedModelData as $name => $value) {
                if ($name === 'password') {
                    $encodedPass = $this->readAttribute($entity, $name);
                    $this->assertEquals($this->encryptor->decryptData($encodedPass), $value);
                } else {
                    $this->assertAttributeEquals($value, $name, $entity);
                }
            }
        } else {
            $form->submit($formData);
            $this->assertNull($form->getData());
        }
    }

    /**
     * @return array
     */
    public function setDataProvider()
    {
        return [
            'should bind correct data except password' => [
                [
                    'imapHost' => 'someHost',
                    'imapPort' => '123',
                    'smtpHost' => '',
                    'smtpPort' => '',
                    'imapEncryption' => 'ssl',
                    'smtpEncryption' => 'ssl',
                    'user' => 'someUser',
                    'password' => self::TEST_PASSWORD,
                ],
                [
                    'imapHost' => 'someHost',
                    'imapPort' => '123',
                    'smtpHost' => '',
                    'smtpPort' => '',
                    'imapEncryption' => 'ssl',
                    'smtpEncryption' => 'ssl',
                    'user' => 'someUser',
                ],
                [
                    'imapHost' => 'someHost',
                    'imapPort' => '123',
                    'smtpHost' => '',
                    'smtpPort' => '',
                    'imapEncryption' => 'ssl',
                    'smtpEncryption' => 'ssl',
                    'user' => 'someUser',
                    'password' => self::TEST_PASSWORD
                ],
            ],
            'should not create empty entity' => [
                [
                    'imapHost' => '',
                    'imapPort' => '',
                    'smtpHost' => '',
                    'smtpPort' => '',
                    'imapEncryption' => '',
                    'smtpEncryption' => '',
                    'user' => '',
                    'password' => ''
                ],
                false,
                false
            ]
        ];
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

        if (!$expectedValid) {
            $constraint = new EmailFolders();

            $this->assertContains($constraint->message, (string)$form->getErrors());
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
        $type = new ConfigurationType($this->encryptor, $this->tokenAccessor, $this->translator);
        $this->assertEquals(ConfigurationType::NAME, $type->getName());
    }
}
