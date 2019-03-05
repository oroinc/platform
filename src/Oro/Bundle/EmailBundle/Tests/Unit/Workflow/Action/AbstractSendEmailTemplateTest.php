<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EmailBundle\Workflow\Action\SendEmail;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractSendEmailTemplateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextAccessor;

    /**
     * @var Processor|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $emailProcessor;

    /**
     * @var EmailRenderer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $renderer;

    /**
     * @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectManager;

    /**
     * @var EmailTemplateInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $emailTemplate;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var EmailTemplateRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectRepository;

    /**
     * @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $validator;

    /**
     * @var SendEmail
     */
    protected $action;

    /**
     * @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityNameResolver;

    /**
     * @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dispatcher;

    /**
     * @var PreferredLanguageProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $languageProvider;

    /**
     * @var EmailOriginHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $emailOriginHelper;

    protected function createDependencyMocks()
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->emailProcessor = $this->createMock(Processor::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->renderer = $this->createMock(EmailRenderer::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->objectRepository = $this->createMock(EmailTemplateRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->objectManager
            ->method('getRepository')
            ->willReturn($this->objectRepository);

        $this->emailTemplate = $this->createMock(EmailTemplate::class);
        $this->languageProvider = $this->createMock(PreferredLanguageProviderInterface::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);
    }

    /**
     * @param mixed $entity
     */
    protected function expectsEntityClass($entity): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->objectManager
            ->method('getClassMetadata')
            ->with(get_class($entity))
            ->willReturn($classMetadata);
        $classMetadata
            ->method('getName')
            ->willReturn(get_class($entity));
    }
}
