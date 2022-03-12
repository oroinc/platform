<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Bundle\ImapBundle\Form\Type\ChoiceAccountType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerInterface;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\Form\Type\ConfigurationTestType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChoiceAccountTypeTest extends FormIntegrationTestCase
{
    private const OAUTH_ACCOUNT_TYPE = 'oauth1';

    /** @var SymmetricCrypterInterface */
    private $encryptor;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var OAuthManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $oauthManagerRegistry;

    /** @var User */
    private $user;

    protected function setUp(): void
    {
        $this->encryptor = new DefaultCrypter('someKey');

        $this->user = new User();
        $this->user->setOrganization(new Organization());

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor->expects(self::any())
            ->method('getUser')
            ->willReturn($this->user);
        $this->tokenAccessor->expects(self::any())
            ->method('getOrganization')
            ->willReturn($this->user->getOrganization());

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($string) {
                return $string . '.trans';
            });

        $oauthManager1 = $this->getOAuthManager('oauth1');
        $oauthManager2 = $this->getOAuthManager('oauth2');
        $this->oauthManagerRegistry = $this->createMock(OAuthManagerRegistry::class);
        $this->oauthManagerRegistry->expects(self::any())
            ->method('getManagers')
            ->willReturn([$oauthManager1, $oauthManager2]);
        $this->oauthManagerRegistry->expects(self::any())
            ->method('isOauthImapEnabled')
            ->willReturnCallback(function ($type) {
                return in_array($type, ['oauth1', 'oauth2']);
            });
        $this->oauthManagerRegistry->expects(self::any())
            ->method('hasManager')
            ->willReturnCallback(function ($type) {
                return in_array($type, ['oauth1', 'oauth2']);
            });
        $this->oauthManagerRegistry->expects(self::any())
            ->method('getManager')
            ->willReturnCallback(function ($type) use ($oauthManager1, $oauthManager2) {
                if ('oauth1' === $type) {
                    return $oauthManager1;
                }
                if ('oauth2' === $type) {
                    return $oauthManager2;
                }
                throw new \InvalidArgumentException(sprintf('The manager for %s" does not exist.', $type));
            });

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
                        new ChoiceAccountType($this->translator, $this->oauthManagerRegistry),
                        new CheckButtonType(),
                        new ConfigurationTestType($this->tokenAccessor),
                        new ConfigurationType($this->encryptor, $this->tokenAccessor, $this->translator),
                        new EmailFolderTreeType()
                    ],
                    [
                        FormType::class => [new TooltipFormExtensionStub($this)]
                    ]
                ),
            ]
        );
    }

    public function testBindValidDataShouldHaveOnlyAccountTypeField()
    {
        $form = $this->factory->create(ChoiceAccountType::class);

        $form->submit([
            'accountType'     => '',
            'userEmailOrigin' => [
                'user'                 => 'test',
                'imapHost'             => '',
                'imapPort'             => '',
                'imapEncryption'       => '',
                'accessTokenExpiresAt' => '',
                'accountType'          => ''
            ],
        ]);

        self::assertEquals('', $form->get('accountType')->getData());

        /** @var AccountTypeModel $entity */
        $entity = $form->getData();

        self::assertNull($entity->getUserEmailOrigin());
        self::assertSame('', $entity->getAccountType());
    }

    public function testBindValidDataShouldHaveCustomAccountTypeAndConnectionTypeFields()
    {
        $now = new \DateTime();
        $form = $this->factory->create(ChoiceAccountType::class);

        $form->submit([
            'accountType'     => self::OAUTH_ACCOUNT_TYPE,
            'userEmailOrigin' => [
                'user'                 => 'test',
                'imapHost'             => '',
                'imapPort'             => '',
                'imapEncryption'       => '',
                'accessTokenExpiresAt' => $now,
                'accessToken'          => 'token',
                'googleAuthCode'       => 'googleAuthCode',
                'accountType'          => self::OAUTH_ACCOUNT_TYPE
            ],
        ]);

        self::assertEquals(
            self::OAUTH_ACCOUNT_TYPE,
            $form->get('accountType')->getData()
        );
        self::assertEquals($this->getUserEmailOrigin([
            'user'                 => 'test',
            'accessTokenExpiresAt' => $now,
            'accessToken'          => 'token',
            'accountType'          => self::OAUTH_ACCOUNT_TYPE
        ]), $form->get('userEmailOrigin')->getData());

        /** @var AccountTypeModel $model */
        $model = $form->getData();

        self::assertInstanceOf(UserEmailOrigin::class, $model->getUserEmailOrigin());
        self::assertSame(self::OAUTH_ACCOUNT_TYPE, $model->getAccountType());
    }

    public function testBindValidDataShouldHaveOtherAccountTypeAndConnectionTypeFields()
    {
        $formData = [
            'accountType'     => 'other',
            'userEmailOrigin' => [
                'user'                 => 'test',
                'imapHost'             => '',
                'imapPort'             => '',
                'imapEncryption'       => '',
                'accessTokenExpiresAt' => new \DateTime(),
                'accessToken'          => '',
                'password'             => '111',
                'accountType'          => self::OAUTH_ACCOUNT_TYPE
            ],
        ];
        $form = $this->factory->create(ChoiceAccountType::class);
        $form->submit($formData);

        self::assertEquals('other', $form->get('accountType')->getData());

        /** @var AccountTypeModel $entity */
        $entity = $form->getData();

        self::assertInstanceOf(UserEmailOrigin::class, $entity->getUserEmailOrigin());
        self::assertSame('other', $entity->getAccountType());
    }

    private function getUserEmailOrigin(array $data): UserEmailOrigin
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
        $userEmailOrigin->setOrganization($this->user->getOrganization());
        $userEmailOrigin->setOwner($this->user);

        return $userEmailOrigin;
    }

    private function getOAuthManager(string $type): OAuthManagerInterface
    {
        $oauthManager = $this->createMock(OAuthManagerInterface::class);
        $oauthManager->expects(self::any())
            ->method('getType')
            ->willReturn($type);
        $oauthManager->expects(self::any())
            ->method('isOAuthEnabled')
            ->willReturn(true);
        $oauthManager->expects(self::any())
            ->method('getConnectionFormTypeClass')
            ->willReturn(ConfigurationTestType::class);

        return $oauthManager;
    }
}
