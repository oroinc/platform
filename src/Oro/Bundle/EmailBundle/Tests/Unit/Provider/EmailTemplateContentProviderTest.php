<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateContentProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Error\Error;

class EmailTemplateContentProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private EmailTemplateRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    private EmailRenderer|\PHPUnit\Framework\MockObject\MockObject $emailRenderer;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private EmailTemplateContentProvider $provider;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EmailTemplateRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($this->repository);

        $this->emailRenderer = $this->createMock(EmailRenderer::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new EmailTemplateContentProvider(
            $doctrine,
            $this->emailRenderer,
            new PropertyAccessor(),
            $this->logger
        );
    }

    /**
     * @dataProvider repositoryExceptionDataProvider
     */
    public function testGetTemplateContentRepositoryException(\Throwable $exception): void
    {
        $criteria = new EmailTemplateCriteria('test_template');
        $localization = new Localization();
        $templateParams = ['any-key' => 'any-val'];

        $this->repository->expects(self::once())
            ->method('findWithLocalizations')
            ->with($criteria)
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                $this->isType('string'),
                [
                    'exception' => $exception,
                    'criteria' => $criteria,
                ]
            );

        $this->expectException(EmailTemplateNotFoundException::class);
        $this->provider->getTemplateContent($criteria, $localization, $templateParams);
    }

    public function repositoryExceptionDataProvider(): array
    {
        return [
            NoResultException::class => [
                'exception' => new NoResultException(),
            ],
            NonUniqueResultException::class => [
                'exception' => new NonUniqueResultException(),
            ],
        ];
    }

    public function testGetTemplateContentRendererException(): void
    {
        $criteria = new EmailTemplateCriteria('test_template');
        $localization = new Localization();
        $templateParams = ['any-key' => 'any-val'];

        $emailTemplate = new EmailTemplate();
        $this->repository->expects(self::once())
            ->method('findWithLocalizations')
            ->with($criteria)
            ->willReturn($emailTemplate);

        $exception = new Error('Some error');
        $this->emailRenderer->expects(self::once())
            ->method('compileMessage')
            ->with(
                $this->isInstanceOf(EmailTemplateModel::class),
                $templateParams
            )
            ->willThrowException($exception);

        $this->expectException(EmailTemplateCompilationException::class);
        $this->provider->getTemplateContent($criteria, $localization, $templateParams);
    }

    public function testGetTemplateContent(): void
    {
        $criteria = new EmailTemplateCriteria('test_template');
        $templateParams = ['any-key' => 'any-val'];

        /** @var Localization $localizationRoot */
        $localizationRoot = $this->getEntity(Localization::class, ['id' => 1]);

        /** @var Localization $localizationChildrenA */
        $localizationChildrenA = $this->getEntity(
            Localization::class,
            ['id' => 2, 'parentLocalization' => $localizationRoot]
        );

        /** @var Localization $localizationChildrenB */
        $localizationChildrenB = $this->getEntity(
            Localization::class,
            ['id' => 3, 'parentLocalization' => $localizationChildrenA]
        );

        $emailTemplate = new EmailTemplate();
        $emailTemplate
            ->setSubject('Not used default subject')
            ->setContent('Default content');

        // Not added template localization for children B for testing fallback without exist template localization
        $emailTemplate->addTranslation(
            (new EmailTemplateTranslation())
                ->setLocalization($localizationChildrenA)
                ->setSubject('Localized subject')
                ->setSubjectFallback(false)
                ->setContent('Not used content')
                ->setContentFallback(true)
        );

        $this->repository->expects(self::once())
            ->method('findWithLocalizations')
            ->with($criteria)
            ->willReturn($emailTemplate);

        $this->emailRenderer->expects(self::once())
            ->method('compileMessage')
            ->with(
                $this->equalTo(
                    (new EmailTemplateModel())
                        ->setType(EmailTemplateModel::CONTENT_TYPE_HTML)
                        ->setSubject('Localized subject')
                        ->setContent('Default content')
                ),
                $templateParams
            )
            ->willReturn([
                'Compiled subject',
                'Compiled content',
            ]);

        $model = $this->provider->getTemplateContent($criteria, $localizationChildrenB, $templateParams);
        self::assertEquals(
            (new EmailTemplateModel())
                ->setType(EmailTemplateModel::CONTENT_TYPE_HTML)
                ->setSubject('Compiled subject')
                ->setContent('Compiled content'),
            $model
        );
    }
}
