<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\Form\FormFactoryInterface;

class EmailTemplateRenderingSubscriberTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadUserData::class,
            '@OroEmailBundle/Tests/Functional/Form/Extension/DataFixtures/EmailTemplateRenderingSubscriber.yml',
        ]);

        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
    }

    protected function tearDown(): void
    {
        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $this->switchUserLocalization($user, null);
    }

    public function testRegularEmailTemplateIsCompiled(): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var EmailTemplateEntity $emailTemplateEntity */
        $emailTemplateEntity = $this->getReference('email_template_regular');
        $emailModel = (new EmailModel())
            ->setFrom('no-reply@exmaple.com')
            ->setEntityClass(User::class)
            ->setEntityId($user->getId())
            ->setTemplate($emailTemplateEntity);

        $this->formFactory->create(EmailType::class, $emailModel);

        self::assertEquals('Email Template Regular', $emailModel->getSubject());
        self::assertStringContainsString('Email Template Regular Content', $emailModel->getBody());
    }

    public function testRegularEmailTemplateIsCompiledInDifferentLocalization(): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var Localization $localizationDe */
        $localizationDe = $this->getReference('localization_de');

        $this->switchUserLocalization($user, $localizationDe);

        /** @var EmailTemplateEntity $emailTemplateEntity */
        $emailTemplateEntity = $this->getReference('email_template_regular');
        $emailModel = (new EmailModel())
            ->setFrom('no-reply@exmaple.com')
            ->setTo([$user->getEmail()])
            ->setEntityClass(User::class)
            ->setEntityId($user->getId())
            ->setTemplate($emailTemplateEntity);

        $this->formFactory->create(EmailType::class, $emailModel);

        self::assertEquals('Email Template (DE) Regular', $emailModel->getSubject());
        self::assertStringContainsString('Email Template (DE) Regular Content', $emailModel->getBody());
    }

    public function testExtendedEmailTemplateIsCompiled(): void
    {
        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $template = $this->getReference('email_template_extended');
        $emailModel = (new EmailModel())
            ->setFrom('no-reply@exmaple.com')
            ->setEntityClass(User::class)
            ->setEntityId($user->getId())
            ->setTemplate($template);

        $this->formFactory->create(EmailType::class, $emailModel);

        self::assertEquals('Email Template Extended', $emailModel->getSubject());
        self::assertStringContainsString('Email Template Base Content', $emailModel->getBody());
        self::assertStringContainsString('Email Template Extended Content', $emailModel->getBody());
    }

    public function testExtendedEmailTemplateIsCompiledInDifferentLocalization(): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var Localization $localizationDe */
        $localizationDe = $this->getReference('localization_de');

        $this->switchUserLocalization($user, $localizationDe);

        /** @var EmailTemplateEntity $emailTemplateEntity */
        $emailTemplateEntity = $this->getReference('email_template_extended');
        $emailModel = (new EmailModel())
            ->setFrom('no-reply@exmaple.com')
            ->setTo([$user->getEmail()])
            ->setEntityClass(User::class)
            ->setEntityId($user->getId())
            ->setTemplate($emailTemplateEntity);

        $this->formFactory->create(EmailType::class, $emailModel);

        self::assertEquals('Email Template (DE) Extended', $emailModel->getSubject());
        self::assertStringContainsString('Email Template (DE) Base', $emailModel->getBody());
        self::assertStringContainsString('Email Template (DE) Extended Content', $emailModel->getBody());
    }

    private function switchUserLocalization(User $user, ?Localization $localization): void
    {
        $userConfigManager = self::getConfigManager('user');
        $userConfigManager->set(
            Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
            $localization?->getId(),
            $user
        );
        $userConfigManager->flush($user);
    }
}
