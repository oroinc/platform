<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ImapBundle\Form\Type\ChoiceAccountType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationGmailType;
use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class ChoiceAccountTypeTest extends FormIntegrationTestCase
{
    /** @var Mcrypt */
    protected $encryptor;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $userConfigManager;

    protected function setUp()
    {
        $this->encryptor = new Mcrypt('someKey');

        $user = $this->getUser();

        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');

        $user->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->securityFacade->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getUser()
    {
        return $this->getMockBuilder('\StdClass')->setMethods(['getOrganization'])->getMock();
    }

    protected function getExtensions()
    {
        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        'oro_imap_configuration_check' => new CheckButtonType(),
                        'oro_imap_configuration_gmail' => new ConfigurationGmailType(
                            $this->translator,
                            $this->userConfigManager,
                            $this->securityFacade
                        ),
                        'oro_imap_configuration' => new ConfigurationType(
                            $this->encryptor,
                            $this->securityFacade,
                            $this->translator
                        ),
                        'oro_email_email_folder_tree' => new EmailFolderTreeType(),
                    ],
                    [
                        'form' => [new TooltipFormExtension($this->configProvider, $this->translator)],
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
        $type = new ChoiceAccountType($this->translator);
        $form = $this->factory->create($type);
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
                        'accessTokenExpiresAt' => new \DateTime(),
                        'accessToken' => 'token',
                        'googleAuthCode' => 'googleAuthCode'
                    ],
                ],
                [
                    'accountType' => 'gmail',
                    'userEmailOrigin' => $this->getUserEmailOrigin([
                        'user' => 'test',
                        'accessTokenExpiresAt' => new \DateTime(),
                        'googleAuthCode' => 'googleAuthCode',
                        'accessToken' => 'token',
                    ])
                ],
                [
                    'accountType' => 'gmail',
                    'userEmailOrigin' => $this->getUserEmailOrigin([
                        'user' => 'test',
                        'accessTokenExpiresAt' => new \DateTime(),
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
                        'accessTokenExpiresAt' => new \DateTime(),
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
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $userEmailOrigin->setOrganization($organization);

        $userEmailOrigin->setOwner($this->getUser());

        return $userEmailOrigin;
    }

    /**
     * Test name of type
     */
    public function testGetName()
    {
        $type = new ChoiceAccountType($this->translator);
        $this->assertEquals(ChoiceAccountType::NAME, $type->getName());
    }
}
