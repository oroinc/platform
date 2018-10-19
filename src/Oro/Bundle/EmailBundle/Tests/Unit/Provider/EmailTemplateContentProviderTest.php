<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\ORM\NonUniqueResultException;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateContentProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class EmailTemplateContentProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const TEMPLATE_NAME = 'templateName';
    private const LANGUAGE = 'fr_FR';
    private const TEMPLATE_PARAMS = ['some' => 'value'];
    private const SUBJECT = 'subject';
    private const CONTENT = 'content';
    private const COMPILED_SUBJECT = 'compiled subject';
    private const COMPILED_CONTENT = 'compiled content';

    /**
     * @var DoctrineHelper|MockObject
     */
    private $doctrineHelper;

    /**
     * @var EmailRenderer|MockObject
     */
    private $emailRenderer;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var EmailTemplateContentProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->emailRenderer = $this->createMock(EmailRenderer::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->provider = new EmailTemplateContentProvider($this->doctrineHelper, $this->emailRenderer);
        $this->provider->setLogger($this->logger);
    }

    public function testGetTemplateContentWhenEmailTemplateIsNotFound(): void
    {
        $criteria = new EmailTemplateCriteria(self::TEMPLATE_NAME);

        $emailTemplateRepository = $this->createMock(EmailTemplateRepository::class);
        $emailTemplateRepository
            ->expects($this->once())
            ->method('findOneLocalized')
            ->with($criteria, self::LANGUAGE)
            ->willReturn(null);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnMap([
                [EmailTemplate::class, $emailTemplateRepository]
            ]);

        $this->expectException(EmailTemplateNotFoundException::class);
        $this->provider->getTemplateContent($criteria, self::LANGUAGE, self::TEMPLATE_PARAMS);
    }

    public function testGetTemplateContentWhenNonUniqueResultException(): void
    {
        $criteria = new EmailTemplateCriteria(self::TEMPLATE_NAME);

        $nonUniqueResultException = new NonUniqueResultException();
        $emailTemplateRepository = $this->createMock(EmailTemplateRepository::class);
        $emailTemplateRepository
            ->expects($this->once())
            ->method('findOneLocalized')
            ->with($criteria, self::LANGUAGE)
            ->willThrowException($nonUniqueResultException);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnMap([
                [EmailTemplate::class, $emailTemplateRepository]
            ]);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Could not find unique email template for the given criteria');

        $this->expectException(EmailTemplateNotFoundException::class);
        $this->provider->getTemplateContent($criteria, self::LANGUAGE, self::TEMPLATE_PARAMS);
    }

    public function testGetTemplateContentWhenCompileMessageFails(): void
    {
        $criteria = new EmailTemplateCriteria(self::TEMPLATE_NAME);

        $emailTemplateEntity = $this->getEntity(EmailTemplate::class, [
            'type' => EmailTemplate::TYPE_HTML,
            'subject' => self::SUBJECT,
            'content' => self::CONTENT
        ]);

        $emailTemplateRepository = $this->createMock(EmailTemplateRepository::class);
        $emailTemplateRepository
            ->expects($this->once())
            ->method('findOneLocalized')
            ->with($criteria, self::LANGUAGE)
            ->willReturn($emailTemplateEntity);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnMap([
                [EmailTemplate::class, $emailTemplateRepository]
            ]);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->matchesRegularExpression('/Rendering of email template .* failed/'));

        $twigException = new \Twig_Error('Some error');
        $this->emailRenderer
            ->expects($this->once())
            ->method('compileMessage')
            ->with($emailTemplateEntity, self::TEMPLATE_PARAMS)
            ->willThrowException($twigException);

        $this->expectException(EmailTemplateCompilationException::class);
        $this->provider->getTemplateContent($criteria, self::LANGUAGE, self::TEMPLATE_PARAMS);
    }

    public function testGetTemplateContent(): void
    {
        $criteria = new EmailTemplateCriteria(self::TEMPLATE_NAME);

        $emailTemplateEntity = $this->getEntity(EmailTemplate::class, [
            'type' => EmailTemplate::TYPE_HTML,
            'subject' => self::SUBJECT,
            'content' => self::CONTENT
        ]);

        $emailTemplateRepository = $this->createMock(EmailTemplateRepository::class);
        $emailTemplateRepository
            ->expects($this->once())
            ->method('findOneLocalized')
            ->with($criteria, self::LANGUAGE)
            ->willReturn($emailTemplateEntity);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnMap([
               [EmailTemplate::class, $emailTemplateRepository]
            ]);

        $this->emailRenderer
            ->expects($this->once())
            ->method('compileMessage')
            ->with($emailTemplateEntity, self::TEMPLATE_PARAMS)
            ->willReturn([self::COMPILED_SUBJECT, self::COMPILED_CONTENT]);

        $expectedEmailTemplateModel = (new EmailTemplateModel())
            ->setSubject(self::COMPILED_SUBJECT)
            ->setContent(self::COMPILED_CONTENT)
            ->setType(EmailTemplateModel::CONTENT_TYPE_HTML);

        self::assertEquals(
            $expectedEmailTemplateModel,
            $this->provider->getTemplateContent($criteria, self::LANGUAGE, self::TEMPLATE_PARAMS)
        );
    }
}
