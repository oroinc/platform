<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationGmailType;
use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ConfigurationGmailTypeTest extends FormIntegrationTestCase
{
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
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->setMethods(['getOrganization'])
            ->getMock();

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

    protected function getExtensions()
    {
        $type = new ConfigurationGmailType($this->translator, $this->userConfigManager, $this->tokenAccessor);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        CheckButtonType::class => new CheckButtonType(),
                        EmailFolderTreeType::class => new EmailFolderTreeType(),
                        ConfigurationGmailType::class => $type
                    ],
                    [
                        FormType::class => [new TooltipFormExtension($this->configProvider, $this->translator)],
                    ]
                ),
            ]
        );
    }

    /**
     * Test default values for form ConfigurationGmailType
     */
    public function testDefaultData()
    {
        $form = $this->factory->create(ConfigurationGmailType::class);

        $expectedViewData = [
            'imapHost' => GmailImap::DEFAULT_GMAIL_HOST,
            'imapPort' => GmailImap::DEFAULT_GMAIL_PORT,
            'imapEncryption' => GmailImap::DEFAULT_GMAIL_SSL,
            'smtpHost' => GmailImap::DEFAULT_GMAIL_SMTP_HOST,
            'smtpPort' => GmailImap::DEFAULT_GMAIL_SMTP_PORT,
            'smtpEncryption' => GmailImap::DEFAULT_GMAIL_SMTP_SSL
        ];

        foreach ($expectedViewData as $name => $value) {
            $this->assertEquals($value, $form->get($name)->getData());
        }
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
        $form = $this->factory->create(ConfigurationGmailType::class);
        if ($expectedViewData) {
            $form->submit($formData);
            foreach ($expectedViewData as $name => $value) {
                $this->assertEquals($value, $form->get($name)->getData());
            }

            $entity = $form->getData();
            foreach ($expectedModelData as $name => $value) {
                $this->assertAttributeEquals($value, $name, $entity);
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
            'should bind correct data' => [
                [
                    'user' => 'test',
                    'imapHost' => 'imap.gmail.com',
                    'imapPort' => '993',
                    'imapEncryption' => 'ssl',
                    'smtpHost' => 'smtp.gmail.com',
                    'smtpPort' => '993',
                    'smtpEncryption' => 'ssl',
                    'accessTokenExpiresAt' => $accessTokenExpiresAt,
                    'accessToken' => '1',
                    'refreshToken' => '111'
                ],
                [
                    'user' => 'test',
                    'imapHost' => 'imap.gmail.com',
                    'imapPort' => '993',
                    'imapEncryption' => 'ssl',
                    'smtpHost' => 'smtp.gmail.com',
                    'smtpPort' => '993',
                    'smtpEncryption' => 'ssl',
                    'accessTokenExpiresAt' => $accessTokenExpiresAt,
                    'accessToken' => '1',
                    'refreshToken' => '111'
                ],
                [
                    'user' => 'test',
                    'imapHost' => 'imap.gmail.com',
                    'imapPort' => '993',
                    'imapEncryption' => 'ssl',
                    'smtpHost' => 'smtp.gmail.com',
                    'smtpPort' => '993',
                    'smtpEncryption' => 'ssl',
                    'accessTokenExpiresAt' => $accessTokenExpiresAt,
                    'accessToken' => '1',
                    'refreshToken' => '111'
                ],
            ],
        ];
    }
}
