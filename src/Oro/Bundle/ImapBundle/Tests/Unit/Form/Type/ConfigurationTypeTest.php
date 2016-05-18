<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ConfigurationTypeTest extends FormIntegrationTestCase
{
    const TEST_PASSWORD = 'somePassword';

    /** @var Mcrypt */
    protected $encryptor;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    protected function setUp()
    {
        $this->encryptor = new Mcrypt('someKey');

        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->setMethods(['getOrganization'])
            ->getMock();

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

        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->setMethods(['hasConfig', 'getConfig', 'get'])
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    protected function getExtensions()
    {
        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        'oro_imap_configuration_check' => new CheckButtonType(),
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
     *
     * @param array      $expectedModelData
     *
     * @dataProvider setDataProvider
     */
    public function testBindValidData($formData, $expectedViewData, $expectedModelData)
    {
        $type = new ConfigurationType($this->encryptor, $this->securityFacade, $this->translator);
        $form = $this->factory->create($type);
        if ($expectedViewData) {
            $form->submit($formData);
            foreach ($expectedViewData as $name => $value) {
                $this->assertEquals($value, $form->get($name)->getData());
            }

            $entity = $form->getData();
            foreach ($expectedModelData as $name => $value) {
                if ($name == 'password') {
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
        return array(
            'should bind correct data except password' => array(
                array(
                    'imapHost'       => 'someHost',
                    'imapPort'       => '123',
                    'smtpHost'       => '',
                    'smtpPort'       => '',
                    'imapEncryption' => 'ssl',
                    'smtpEncryption' => 'ssl',
                    'user'           => 'someUser',
                    'password'       => self::TEST_PASSWORD,
                ),
                array(
                    'imapHost'       => 'someHost',
                    'imapPort'       => '123',
                    'smtpHost'       => '',
                    'smtpPort'       => '',
                    'imapEncryption' => 'ssl',
                    'smtpEncryption' => 'ssl',
                    'user'           => 'someUser',
                ),
                array(
                    'imapHost'        => 'someHost',
                    'imapPort'        => '123',
                    'smtpHost'        => '',
                    'smtpPort'        => '',
                    'imapEncryption'  => 'ssl',
                    'smtpEncryption'  => 'ssl',
                    'user'            => 'someUser',
                    'password'        => self::TEST_PASSWORD
                ),
            ),
            'should not create empty entity' => array(
                array(
                    'imapHost'       => '',
                    'imapPort'       => '',
                    'smtpHost'       => '',
                    'smtpPort'       => '',
                    'imapEncryption' => '',
                    'smtpEncryption' => '',
                    'user'           => '',
                    'password'       => ''
                ),
                false,
                false
            )
        );
    }

    /**
     * If submitted empty password, it should be populated from old entity
     */
    public function testBindEmptyPassword()
    {
        $type = new ConfigurationType($this->encryptor, $this->securityFacade, $this->translator);
        $form = $this->factory->create($type);

        $entity = new UserEmailOrigin();
        $entity->setPassword(self::TEST_PASSWORD);

        $form->setData($entity);
        $form->submit(
            array(
                'imapHost'       => 'someHost',
                'imapPort'       => '123',
                'smtpHost'       => '',
                'smtpPort'       => '',
                'imapEncryption' => 'ssl',
                'smtpEncryption' => 'ssl',
                'user'           => 'someUser',
                'password'       => ''
            )
        );

        $this->assertEquals(self::TEST_PASSWORD, $entity->getPassword());
    }

    /**
     * In case when user or host field was changed new configuration should be created
     * and old one will be not active.
     */
    public function testCreatingNewConfiguration()
    {
        $type = new ConfigurationType($this->encryptor, $this->securityFacade, $this->translator);
        $form = $this->factory->create($type);

        $entity = new UserEmailOrigin();
        $entity->setImapHost('someHost');
        $this->assertTrue($entity->isActive());

        $form->setData($entity);
        $form->submit(
            array(
                'useImap'        => 1,
                'imapHost'       => 'someHost',
                'imapPort'       => '123',
                'smtpHost'       => '',
                'smtpPort'       => '',
                'imapEncryption' => 'ssl',
                'smtpEncryption' => 'ssl',
                'user'           => 'someUser',
                'password'       => 'somPassword'
            )
        );

        $this->assertNotSame($entity, $form->getData());

        $this->assertInstanceOf('Oro\Bundle\ImapBundle\Entity\UserEmailOrigin', $form->getData());
        $this->assertTrue($form->getData()->isActive());
    }

    /**
     * In case when user or host field was changed new configuration should NOT be created if imap and smtp
     * are inactive.
     */
    public function testNotCreatingNewConfigurationWhenImapInactive()
    {
        $type = new ConfigurationType($this->encryptor, $this->securityFacade, $this->translator);
        $form = $this->factory->create($type);

        $entity = new UserEmailOrigin();
        $entity->setImapHost('someHost');
        $this->assertTrue($entity->isActive());

        $form->setData($entity);
        $form->submit(
            array(
                'useImap'        => 0,
                'useSmtp'        => 0,
                'imapHost'       => 'someHost',
                'imapPort'       => '123',
                'smtpHost'       => '',
                'smtpPort'       => '',
                'imapEncryption' => 'ssl',
                'smtpEncryption' => 'ssl',
                'user'           => 'someUser',
                'password'       => 'somPassword'
            )
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
        $type = new ConfigurationType($this->encryptor, $this->securityFacade, $this->translator);
        $form = $this->factory->create($type);

        $entity = new UserEmailOrigin();
        $this->assertTrue($entity->isActive());

        $form->setData($entity);
        $form->submit(
            array(
                'imapHost'       => '',
                'imapPort'       => '',
                'smtpHost'       => '',
                'smtpPort'       => '',
                'imapEncryption' => '',
                'smtpEncryption' => '',
                'user'           => '',
                'password'       => ''
            )
        );

        $this->assertNotSame($entity, $form->getData());
        $this->assertNotInstanceOf('Oro\Bundle\ImapBundle\Entity\UserEmailOrigin', $form->getData());
        $this->assertNull($form->getData());
    }

    public function testGetName()
    {
        $type = new ConfigurationType($this->encryptor, $this->securityFacade, $this->translator);
        $this->assertEquals(ConfigurationType::NAME, $type->getName());
    }
}
