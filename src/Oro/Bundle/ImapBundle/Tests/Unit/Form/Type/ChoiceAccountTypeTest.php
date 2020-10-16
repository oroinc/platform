<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Bundle\ImapBundle\Form\Type\ChoiceAccountType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Manager\OAuth2ManagerRegistry;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\Form\Type\ConfigurationTestType;
use Oro\Bundle\ImapBundle\Tests\Unit\TestCase\OauthManagerRegistryAwareInterface;
use Oro\Bundle\ImapBundle\Tests\Unit\TestCase\OauthManagerRegistryAwareTrait;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ChoiceAccountTypeTest extends FormIntegrationTestCase
{
    use OauthManagerRegistryAwareTrait;

    /** @var SymmetricCrypterInterface */
    protected $encryptor;

    /** @var TokenAccessorInterface|MockObject */
    protected $tokenAccessor;

    /** @var Translator|MockObject */
    protected $translator;

    /** @var MockObject */
    protected $configProvider;

    /** @var ConfigManager|MockObject */
    protected $userConfigManager;

    /** @var OAuth2ManagerRegistry|MockObject */
    protected $oauthManagerRegistry;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->encryptor = new DefaultCrypter('someKey');

        $user = $this->getUser();

        $organization = $this->createMock('Oro\Bundle\OrganizationBundle\Entity\Organization');

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

        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($string) {
                return $string . '.trans';
            });

        $this->userConfigManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->setMethods(['hasConfig', 'getConfig', 'get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->oauthManagerRegistry = $this->getManagerRegistryMock();

        parent::setUp();
    }

    /**
     * @return MockObject
     */
    protected function getUser()
    {
        return $this->getMockBuilder('\StdClass')->setMethods(['getOrganization'])->getMock();
    }

    protected function getExtensions()
    {
        $type = new ChoiceAccountType($this->translator, $this->oauthManagerRegistry);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        ChoiceAccountType::class => $type,
                        CheckButtonType::class => new CheckButtonType(),
                        ConfigurationTestType::class => new ConfigurationTestType(
                            $this->translator,
                            $this->userConfigManager,
                            $this->tokenAccessor
                        ),
                        ConfigurationType::class => new ConfigurationType(
                            $this->encryptor,
                            $this->tokenAccessor,
                            $this->translator,
                            $this->oauthManagerRegistry
                        ),
                        EmailFolderTreeType::class => new EmailFolderTreeType(),
                    ],
                    [
                        FormType::class => [new TooltipFormExtension($this->configProvider, $this->translator)],
                    ]
                ),
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->encryptor);
    }

    public function testBindValidDataShouldHaveOnlyAccountTypeField()
    {
        $form = $this->factory->create(ChoiceAccountType::class);

        $form->submit([
            'accountType' => '',
            'userEmailOrigin' => [
                'user' => 'test',
                'imapHost' => '',
                'imapPort' => '',
                'imapEncryption' => '',
                'accessTokenExpiresAt' => '',
                'accountType' => ''
            ],
        ]);

        static::assertEquals('', $form->get('accountType')->getData());

        /** @var AccountTypeModel $entity */
        $entity = $form->getData();

        static::assertNull($entity->getUserEmailOrigin());
        static::assertSame('', $entity->getAccountType());
    }

    public function testBindValidDataShouldHaveCustomAccountTypeAndConnectionTypeFields()
    {
        $now = new \DateTime();
        $form = $this->factory->create(ChoiceAccountType::class);

        $form->submit([
            'accountType' => OauthManagerRegistryAwareInterface::MANAGER_TYPE_DEFAULT,
            'userEmailOrigin' => [
                'user' => 'test',
                'imapHost' => '',
                'imapPort' => '',
                'imapEncryption' => '',
                'accessTokenExpiresAt' => $now,
                'accessToken' => 'token',
                'googleAuthCode' => 'googleAuthCode',
                'accountType' => OauthManagerRegistryAwareInterface::MANAGER_TYPE_DEFAULT
            ],
        ]);

        static::assertEquals(
            OauthManagerRegistryAwareInterface::MANAGER_TYPE_DEFAULT,
            $form->get('accountType')->getData()
        );
        static::assertEquals($this->getUserEmailOrigin([
            'user' => 'test',
            'accessTokenExpiresAt' => $now,
            'accessToken' => 'token',
            'accountType' => OauthManagerRegistryAwareInterface::MANAGER_TYPE_DEFAULT
        ]), $form->get('userEmailOrigin')->getData());

        /** @var AccountTypeModel $model */
        $model = $form->getData();

        static::assertInstanceOf(UserEmailOrigin::class, $model->getUserEmailOrigin());
        static::assertSame(OauthManagerRegistryAwareInterface::MANAGER_TYPE_DEFAULT, $model->getAccountType());
    }

    public function testBindValidDataShouldHaveOtherAccountTypeAndConnectionTypeFields()
    {
        $formData = [
            'accountType' => 'other',
            'userEmailOrigin' => [
                'user' => 'test',
                'imapHost' => '',
                'imapPort' => '',
                'imapEncryption' => '',
                'accessTokenExpiresAt' => new \DateTime(),
                'accessToken' => '',
                'password' => '111',
                'accountType' => OauthManagerRegistryAwareInterface::MANAGER_TYPE_DEFAULT
            ],
        ];
        $form = $this->factory->create(ChoiceAccountType::class);
        $form->submit($formData);

        static::assertEquals('other', $form->get('accountType')->getData());

        /** @var AccountTypeModel $entity */
        $entity = $form->getData();

        static::assertInstanceOf(UserEmailOrigin::class, $entity->getUserEmailOrigin());
        static::assertSame('other', $entity->getAccountType());
    }

    /**
     *  Return UserEmailOrigin entity created with data of $data variable
     */
    protected function getUserEmailOrigin($data)
    {
        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setUser($data['user']);
        $userEmailOrigin->setAccessTokenExpiresAt($data['accessTokenExpiresAt']);

        if (isset($data['password'])) {
            $userEmailOrigin->setPassword($data['password']);
        }
        if (isset($data['accessToken'])) {
            $userEmailOrigin->setAccessToken($data['accessToken']);
        }
        if (isset($data['refreshToken'])) {
            $userEmailOrigin->setRefreshToken($data['refreshToken']);
        }
        if (isset($data['accountType'])) {
            $userEmailOrigin->setAccountType($data['accountType']);
        }
        $organization = $this->createMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $userEmailOrigin->setOrganization($organization);

        $userEmailOrigin->setOwner($this->getUser());

        return $userEmailOrigin;
    }
}
