<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractSendEmailTemplateTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextAccessor;

    /** @var Processor|\PHPUnit\Framework\MockObject\MockObject */
    protected $emailProcessor;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityNameResolver;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $validator;

    /** @var LocalizedTemplateProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $localizedTemplateProvider;

    /** @var EmailOriginHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $emailOriginHelper;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    protected $dispatcher;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $objectManager;

    /** @var EmailTemplate|\PHPUnit\Framework\MockObject\MockObject */
    protected $emailTemplate;

    /** @var EmailTemplateRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $objectRepository;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->emailProcessor = $this->createMock(Processor::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->localizedTemplateProvider = $this->createMock(LocalizedTemplateProvider::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);

        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->objectRepository = $this->createMock(EmailTemplateRepository::class);
        $this->emailTemplate = $this->createMock(EmailTemplate::class);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->objectManager);

        $this->objectManager
            ->method('getRepository')
            ->willReturn($this->objectRepository);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->any())
            ->method('getName')
            ->willReturn(\stdClass::class);

        $this->objectManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($classMetadata);
    }
}
