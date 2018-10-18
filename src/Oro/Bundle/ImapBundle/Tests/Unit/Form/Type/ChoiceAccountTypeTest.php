<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Bundle\ImapBundle\Form\Type\ChoiceAccountType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationGmailType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ChoiceAccountTypeTest extends FormIntegrationTestCase
{
    /** @var SymmetricCrypterInterface */
    protected $encryptor;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $userConfigManager;

    protected function setUp()
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

        parent::setUp();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getUser()
    {
        return $this->getMockBuilder('\StdClass')->setMethods(['getOrganization'])->getMock();
    }

    protected function getExtensions()
    {
        $type = new ChoiceAccountType($this->translator);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        ChoiceAccountType::class => $type,
                        CheckButtonType::class => new CheckButtonType(),
                        ConfigurationGmailType::class => new ConfigurationGmailType(
                            $this->translator,
                            $this->userConfigManager,
                            $this->tokenAccessor
                        ),
                        ConfigurationType::class => new ConfigurationType(
                            $this->encryptor,
                            $this->tokenAccessor,
                            $this->translator
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

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->encryptor);
    }

    /**
     * @param array      $formData
     * @param array|bool $expectedViewData
     * @param array      $expectedModelData
     *
     * @dataProvider setDataProvider
     */
    public function testBindValidData($formData, $expectedViewData, $expectedModelData)
    {
        $form = $this->factory->create(ChoiceAccountType::class);
        if ($expectedViewData) {
            $form->submit($formData);
            foreach ($expectedViewData as $name => $value) {
                $this->assertEquals($value, $form->get($name)->getData());
            }

            $entity = $form->getData();
            foreach ($expectedModelData as $name => $value) {
                if ($name === 'userEmailOrigin') {
                    $userEmailOrigin = $this->readAttribute($entity, $name);

                    if ($userEmailOrigin) {
                        $password = $this->encryptor->decryptData($userEmailOrigin->getPassword());
                        $userEmailOrigin->setPassword($password);
                    }

                    $this->assertAttributeEquals($userEmailOrigin, $name, $entity);
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
        $accessTokenExpiresAt = new \DateTime();

        return [
            'should have only accountType field' => [
                [
                    'accountType' => '',
                    'userEmailOrigin' => [
                        'user' => 'test',
                        'imapHost' => '',
                        'imapPort' => '',
                        'imapEncryption' => '',
                        'accessTokenExpiresAt' => '',
                    ],
                ],
                [
                    'accountType' => ''
                ],
                [
                    'accountType' => '',
                    'userEmailOrigin' => null
                ],
            ],
            'should have accountType field and ConnectionGmailType' => [
                [
                    'accountType' => 'gmail',
                    'userEmailOrigin' => [
                        'user' => 'test',
                        'imapHost' => '',
                        'imapPort' => '',
                        'imapEncryption' => '',
                        'accessTokenExpiresAt' => $accessTokenExpiresAt,
                        'accessToken' => 'token',
                        'googleAuthCode' => 'googleAuthCode'
                    ],
                ],
                [
                    'accountType' => 'gmail',
                    'userEmailOrigin' => $this->getUserEmailOrigin([
                        'user' => 'test',
                        'accessTokenExpiresAt' => $accessTokenExpiresAt,
                        'googleAuthCode' => 'googleAuthCode',
                        'accessToken' => 'token',
                    ])
                ],
                [
                    'accountType' => 'gmail',
                    'userEmailOrigin' => $this->getUserEmailOrigin([
                        'user' => 'test',
                        'accessTokenExpiresAt' => $accessTokenExpiresAt,
                        'googleAuthCode' => 'googleAuthCode',
                        'accessToken' => 'token'
                    ])
                ],
            ],
            'should have accountType field and ConnectionType' => [
                [
                    'accountType' => 'other',
                    'userEmailOrigin' => [
                        'user' => 'test',
                        'imapHost' => '',
                        'imapPort' => '',
                        'imapEncryption' => '',
                        'accessTokenExpiresAt' => $accessTokenExpiresAt,
                        'accessToken' => '',
                        'googleAuthCode' => 'googleAuthCode',
                        'password' => '111'
                    ],
                ],
                [
                    'accountType' => 'other',
                ],
                [
                    'accountType' => 'other',
                    'userEmailOrigin' => $this->getUserEmailOrigin([
                        'user' => 'test',
                        'accessTokenExpiresAt' => null,
                        'googleAuthCode' => null,
                        'password' => '111'
                    ])
                ],
            ]
        ];
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
        $organization = $this->createMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $userEmailOrigin->setOrganization($organization);

        $userEmailOrigin->setOwner($this->getUser());

        return $userEmailOrigin;
    }
}
