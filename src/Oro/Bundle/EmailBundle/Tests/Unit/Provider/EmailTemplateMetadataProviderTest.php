<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateMetadataProvider;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EmailTemplateMetadataProviderTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;

    private EmailTemplateMetadataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new EmailTemplateMetadataProvider($this->doctrine);
    }

    public function testGetEmailTemplateMetadataReturnsNullWhenTemplateNotFound(): void
    {
        $emailTemplateName = 'sample_template';

        $repository = $this->createMock(EmailTemplateRepository::class);
        $repository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with(new EmailTemplateCriteria($emailTemplateName))
            ->willReturn(null);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository);

        self::assertNull($this->provider->getEmailTemplateMetadata($emailTemplateName));
    }

    public function testGetEmailTemplateMetadataReturnsNullWhenEmailTemplateNameIsNull(): void
    {
        $emailTemplateModel = (new EmailTemplateModel())->setName(null);

        $this->doctrine
            ->expects(self::never())
            ->method('getRepository');

        self::assertNull($this->provider->getEmailTemplateMetadata($emailTemplateModel));
    }

    /**
     * @dataProvider emailTemplateMetadataProvider
     */
    public function testGetEmailTemplateMetadataReturnsMetadataWhenTemplateFound(
        EmailTemplate $emailTemplate,
        array $expectedMetadata
    ): void {
        $emailTemplateName = 'sample_template';

        $repository = $this->createMock(EmailTemplateRepository::class);
        $repository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with(new EmailTemplateCriteria($emailTemplateName))
            ->willReturn($emailTemplate);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository);

        self::assertSame($expectedMetadata, $this->provider->getEmailTemplateMetadata($emailTemplateName));
    }

    public function testGetEmailTemplateMetadataReturnsMetadataWhenEmailTemplateCriteriaGiven(): void
    {
        $emailTemplateCriteria = new EmailTemplateCriteria('sample_template', User::class);
        $emailTemplateEntity = new EmailTemplate('sample_template', '', 'html', false);

        $repository = $this->createMock(EmailTemplateRepository::class);
        $repository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with($emailTemplateCriteria)
            ->willReturn($emailTemplateEntity);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository);

        self::assertSame(
            [
                EmailTemplateMetadataProvider::ENTITY_NAME => null,
                EmailTemplateMetadataProvider::IS_SYSTEM => false,
                EmailTemplateMetadataProvider::IS_EDITABLE => true,
                EmailTemplateMetadataProvider::IS_VISIBLE => true,
            ],
            $this->provider->getEmailTemplateMetadata($emailTemplateCriteria)
        );
    }

    public function testGetEmailTemplateMetadataReturnsMetadataWhenEmailTemplateGiven(): void
    {
        $emailTemplateModel = new EmailTemplateModel('sample_template');
        $emailTemplateEntity = new EmailTemplate('sample_template', '', 'html', true);

        $repository = $this->createMock(EmailTemplateRepository::class);
        $repository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with(new EmailTemplateCriteria('sample_template'))
            ->willReturn($emailTemplateEntity);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository);

        self::assertSame(
            [
                EmailTemplateMetadataProvider::ENTITY_NAME => null,
                EmailTemplateMetadataProvider::IS_SYSTEM => true,
                EmailTemplateMetadataProvider::IS_EDITABLE => false,
                EmailTemplateMetadataProvider::IS_VISIBLE => true,
            ],
            $this->provider->getEmailTemplateMetadata($emailTemplateModel)
        );
    }

    public static function emailTemplateMetadataProvider(): iterable
    {
        $systemTemplate = new EmailTemplate('system_template', '', 'html', true);
        $systemTemplate->setEntityName(User::class);

        yield 'system template, not editable, visible, with entity name' => [
            $systemTemplate,
            [
                EmailTemplateMetadataProvider::ENTITY_NAME => User::class,
                EmailTemplateMetadataProvider::IS_SYSTEM => true,
                EmailTemplateMetadataProvider::IS_EDITABLE => false,
                EmailTemplateMetadataProvider::IS_VISIBLE => true,
            ],
        ];

        $userTemplate = new EmailTemplate('user_template', '', 'html', false);
        $userTemplate->setVisible(false);

        yield 'user template, editable, not visible, no entity name' => [
            $userTemplate,
            [
                EmailTemplateMetadataProvider::ENTITY_NAME => null,
                EmailTemplateMetadataProvider::IS_SYSTEM => false,
                EmailTemplateMetadataProvider::IS_EDITABLE => true,
                EmailTemplateMetadataProvider::IS_VISIBLE => false,
            ],
        ];

        $editableSystemTemplate = new EmailTemplate('editable_system_template', '', 'html', true);
        $editableSystemTemplate->setEntityName(Email::class);
        $editableSystemTemplate->setIsEditable(true);

        yield 'system template, editable, visible, with entity name' => [
            $editableSystemTemplate,
            [
                EmailTemplateMetadataProvider::ENTITY_NAME => Email::class,
                EmailTemplateMetadataProvider::IS_SYSTEM => true,
                EmailTemplateMetadataProvider::IS_EDITABLE => true,
                EmailTemplateMetadataProvider::IS_VISIBLE => true,
            ],
        ];

        $hiddenSystemTemplate = new EmailTemplate('hidden_system_template', '', 'html', true);
        $hiddenSystemTemplate->setVisible(false);

        yield 'system template, not editable, not visible, no entity name' => [
            $hiddenSystemTemplate,
            [
                EmailTemplateMetadataProvider::ENTITY_NAME => null,
                EmailTemplateMetadataProvider::IS_SYSTEM => true,
                EmailTemplateMetadataProvider::IS_EDITABLE => false,
                EmailTemplateMetadataProvider::IS_VISIBLE => false,
            ],
        ];
    }
}
