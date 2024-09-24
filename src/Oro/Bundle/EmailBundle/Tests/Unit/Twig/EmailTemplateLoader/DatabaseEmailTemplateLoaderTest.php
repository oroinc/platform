<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig\EmailTemplateLoader;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\TranslatedEmailTemplateProvider;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader\DatabaseEmailTemplateLoader;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Error\LoaderError;
use Twig\Source;

class DatabaseEmailTemplateLoaderTest extends TestCase
{
    private const LOCALIZATION_ID = 42;

    private TranslatedEmailTemplateProvider $translatedEmailTemplateProvider;

    private DatabaseEmailTemplateLoader $loader;

    private EmailTemplateRepository|MockObject $emailTemplateRepo;

    #[\Override]
    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->translatedEmailTemplateProvider = $this->createMock(TranslatedEmailTemplateProvider::class);

        $this->loader = new DatabaseEmailTemplateLoader(
            $managerRegistry,
            $this->translatedEmailTemplateProvider
        );

        $this->emailTemplateRepo = $this->createMock(EmailTemplateRepository::class);
        $managerRegistry
            ->method('getRepository')
            ->with(EmailTemplateEntity::class)
            ->willReturn($this->emailTemplateRepo);

        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->method('getManagerForClass')
            ->with(Localization::class)
            ->willReturn($this->entityManager);
    }

    public function testExistsWhenNotSupportedNamespace(): void
    {
        $emailTemplateRepo = $this->createMock(EmailTemplateRepository::class);

        $emailTemplateRepo
            ->expects(self::never())
            ->method('isExist');

        self::assertFalse($this->loader->exists('@sample_namespace/sample_name'));
    }

    public function testExistsWhenIsNotNamespaced(): void
    {
        $name = 'sample_name';

        $this->emailTemplateRepo
            ->expects(self::once())
            ->method('isExist')
            ->with(new EmailTemplateCriteria($name))
            ->willReturn(true);

        self::assertTrue($this->loader->exists($name));
    }

    public function testExistsWhenEmptyContext(): void
    {
        $templateName = 'base';
        $name = '@db:/' . $templateName;

        $this->emailTemplateRepo
            ->expects(self::once())
            ->method('isExist')
            ->with(new EmailTemplateCriteria($templateName))
            ->willReturn(true);

        self::assertTrue($this->loader->exists($name));
    }

    public function testExistsWhenNotEmptyContext(): void
    {
        $templateName = 'sample_name';
        $name = '@db:entityName=Acme\Bundle\Entity\SampleEntity&sample_key=sample_value/' . $templateName;

        $this->emailTemplateRepo
            ->expects(self::once())
            ->method('isExist')
            ->with(
                new EmailTemplateCriteria(
                    $templateName,
                    'Acme\Bundle\Entity\SampleEntity'
                ),
                ['sample_key' => 'sample_value']
            )
            ->willReturn(true);

        self::assertTrue($this->loader->exists($name));
    }

    public function testGetEmailTemplateWhenNotEmptyContextAndTemplateNotFound(): void
    {
        $templateName = 'sample_name';
        $name = '@db:entityName=Acme\Bundle\Entity\SampleEntity/' . $templateName;

        $this->emailTemplateRepo
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with(new EmailTemplateCriteria($templateName, 'Acme\Bundle\Entity\SampleEntity'), [])
            ->willReturn(null);

        $this->expectExceptionObject(new LoaderError('Failed to find email template "' . $name . '"'));

        $this->loader->getEmailTemplate($name);
    }

    public function testGetEmailTemplateWhenNotEmptyContextAndFoundTemplate(): void
    {
        $templateName = 'sample_name';
        $name = '@db:entityName=Acme\Bundle\Entity\SampleEntity&localization='
            . self::LOCALIZATION_ID . '/' . $templateName;

        $localization = new LocalizationStub(self::LOCALIZATION_ID);
        $this->entityManager
            ->expects(self::once())
            ->method('getReference')
            ->with(Localization::class, self::LOCALIZATION_ID)
            ->willReturn($localization);

        $emailTemplateEntity = new EmailTemplateEntity();
        $this->emailTemplateRepo
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with(
                new EmailTemplateCriteria(
                    $templateName,
                    'Acme\Bundle\Entity\SampleEntity'
                ),
                ['localization' => $localization]
            )
            ->willReturn($emailTemplateEntity);

        $emailTemplateModel = new EmailTemplateModel();
        $this->translatedEmailTemplateProvider
            ->expects(self::once())
            ->method('getTranslatedEmailTemplate')
            ->with($emailTemplateEntity, $localization)
            ->willReturn($emailTemplateModel);

        self::assertSame($emailTemplateModel, $this->loader->getEmailTemplate($name));
    }

    public function testGetSourceContextWhenNotEmptyContextAndTemplateNotFound(): void
    {
        $templateName = 'sample_name';
        $name = '@db:entityName=Acme\Bundle\Entity\SampleEntity/' . $templateName;

        $this->emailTemplateRepo
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with(new EmailTemplateCriteria($templateName, 'Acme\Bundle\Entity\SampleEntity'), [])
            ->willReturn(null);

        $this->expectExceptionObject(new LoaderError('Failed to find email template "' . $name . '"'));

        $this->loader->getSourceContext($name);
    }

    public function testGetSourceContextWhenNotEmptyContextAndFoundTemplate(): void
    {
        $templateName = 'sample_name';
        $name = '@db:entityName=Acme\Bundle\Entity\SampleEntity&localization='
            . self::LOCALIZATION_ID . '/' . $templateName;

        $localization = new LocalizationStub(self::LOCALIZATION_ID);
        $this->entityManager
            ->expects(self::once())
            ->method('getReference')
            ->with(Localization::class, self::LOCALIZATION_ID)
            ->willReturn($localization);

        $emailTemplateEntity = new EmailTemplateEntity();
        $this->emailTemplateRepo
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with(
                new EmailTemplateCriteria($templateName, 'Acme\Bundle\Entity\SampleEntity'),
                ['localization' => $localization]
            )
            ->willReturn($emailTemplateEntity);

        $emailTemplateModel = (new EmailTemplateModel())
            ->setContent('sample_content');
        $this->translatedEmailTemplateProvider
            ->expects(self::once())
            ->method('getTranslatedEmailTemplate')
            ->with($emailTemplateEntity, $localization)
            ->willReturn($emailTemplateModel);

        self::assertEquals(
            new Source($emailTemplateModel->getContent(), $name),
            $this->loader->getSourceContext($name)
        );
    }
}
