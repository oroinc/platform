<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\PostUpgrade;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyCheckBefore as Event;
use Oro\Bundle\EmailBundle\PostUpgrade\EntityFieldsUsedInEmailTemplatesProvider;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessAnalyzer;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessEntry;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Error\SyntaxError;

final class EntityFieldsUsedInEmailTemplatesProviderTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private TemplateAccessAnalyzer&MockObject $templateAccessAnalyzer;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private EmailTemplateRepository&MockObject $repository;
    private EntityFieldsUsedInEmailTemplatesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->templateAccessAnalyzer = $this->createMock(TemplateAccessAnalyzer::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->repository = $this->createMock(EmailTemplateRepository::class);

        $doctrine
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($this->repository);

        $this->provider = new EntityFieldsUsedInEmailTemplatesProvider(
            $doctrine,
            $this->templateAccessAnalyzer,
            $this->eventDispatcher
        );

        $this->setUpLoggerMock($this->provider);
    }

    public function testGetEntityFieldsUsedInEmailTemplatesReturnsEmptyArrayWhenNoTemplates(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([]));

        $this->templateAccessAnalyzer
            ->expects(self::never())
            ->method('analyzeTemplate');

        $this->assertLoggerNotCalled();

        self::assertSame([], $this->provider->getEntityFieldsUsedInEmailTemplates());
    }

    public function testGetEntityFieldsUsedInEmailTemplatesSkipsNonStringContent(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setContent(null);
        $emailTemplate->setSubject(null);

        $this->repository
            ->expects(self::once())
            ->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([$emailTemplate]));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(Event::class));

        $this->templateAccessAnalyzer
            ->expects(self::never())
            ->method('analyzeTemplate');

        $this->assertLoggerNotCalled();

        self::assertSame([], $this->provider->getEntityFieldsUsedInEmailTemplates());
    }

    public function testGetEntityFieldsUsedInEmailTemplatesSkipsEmptyStringContent(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setContent('');
        $emailTemplate->setSubject('');

        $this->repository
            ->expects(self::once())
            ->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([$emailTemplate]));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch');

        $this->templateAccessAnalyzer
            ->expects(self::never())
            ->method('analyzeTemplate');

        $this->assertLoggerNotCalled();

        self::assertSame([], $this->provider->getEntityFieldsUsedInEmailTemplates());
    }

    public function testGetEntityFieldsUsedInEmailTemplatesReturnsPropertyAccesses(): void
    {
        $entityClass = \stdClass::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEntityName($entityClass);
        $emailTemplate->setContent('{{ entity.firstName }}');
        $emailTemplate->setSubject('Hello {{ entity.lastName }}');

        $this->repository
            ->expects(self::once())
            ->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([$emailTemplate]));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(Event::class));

        $entryFirstName = new TemplateAccessEntry(
            className: $entityClass,
            variableName: 'entity',
            attributeName: 'firstName',
            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            lineNumber: 1
        );
        $entryLastName = new TemplateAccessEntry(
            className: $entityClass,
            variableName: 'entity',
            attributeName: 'lastName',
            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            lineNumber: 1
        );

        $this->templateAccessAnalyzer
            ->expects(self::exactly(2))
            ->method('analyzeTemplate')
            ->willReturnMap([
                ['{{ entity.firstName }}', ['entity' => $entityClass], [$entryFirstName]],
                ['Hello {{ entity.lastName }}', ['entity' => $entityClass], [$entryLastName]],
            ]);

        $this->assertLoggerNotCalled();

        $result = $this->provider->getEntityFieldsUsedInEmailTemplates();

        self::assertEquals(
            [
                ['entity' => $entityClass, 'field' => 'firstName'],
                ['entity' => $entityClass, 'field' => 'lastName'],
            ],
            $result
        );
    }

    public function testGetEntityFieldsUsedInEmailTemplatesDeduplicatesEntries(): void
    {
        $entityClass = \stdClass::class;

        $template1 = new EmailTemplate();
        $template1->setEntityName($entityClass);
        $template1->setContent('{{ entity.name }}');
        $template1->setSubject('');

        $template2 = new EmailTemplate();
        $template2->setEntityName($entityClass);
        $template2->setContent('{{ entity.name }}');
        $template2->setSubject('');

        $this->repository
            ->expects(self::once())
            ->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([$template1, $template2]));

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch');

        $entry = new TemplateAccessEntry(
            className: $entityClass,
            variableName: 'entity',
            attributeName: 'name',
            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            lineNumber: 1
        );

        $this->templateAccessAnalyzer
            ->expects(self::exactly(2))
            ->method('analyzeTemplate')
            ->willReturn([$entry]);

        $this->assertLoggerNotCalled();

        $result = $this->provider->getEntityFieldsUsedInEmailTemplates();

        self::assertCount(1, $result);
        self::assertEquals(['entity' => $entityClass, 'field' => 'name'], $result[0]);
    }

    public function testGetEntityFieldsUsedInEmailTemplatesSkipsNonGetterMethodAccessEntries(): void
    {
        $entityClass = \stdClass::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEntityName($entityClass);
        $emailTemplate->setContent('{{ entity.doSomething() }}');
        $emailTemplate->setSubject('');

        $this->repository
            ->expects(self::once())
            ->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([$emailTemplate]));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch');

        $methodEntry = new TemplateAccessEntry(
            className: $entityClass,
            variableName: 'entity',
            attributeName: 'doSomething',
            accessType: TemplateAccessEntry::ACCESS_TYPE_METHOD,
            lineNumber: 1
        );

        $this->templateAccessAnalyzer
            ->expects(self::once())
            ->method('analyzeTemplate')
            ->willReturn([$methodEntry]);

        $this->assertLoggerNotCalled();

        self::assertSame([], $this->provider->getEntityFieldsUsedInEmailTemplates());
    }

    public function testGetEntityFieldsUsedInEmailTemplatesDerivesFieldNameFromGetterMethod(): void
    {
        $entityClass = \stdClass::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEntityName($entityClass);
        $emailTemplate->setContent('{{ entity.getName() }}');
        $emailTemplate->setSubject('');

        $this->repository
            ->expects(self::once())
            ->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([$emailTemplate]));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch');

        $methodEntry = new TemplateAccessEntry(
            className: $entityClass,
            variableName: 'entity',
            attributeName: 'getName',
            accessType: TemplateAccessEntry::ACCESS_TYPE_METHOD,
            lineNumber: 1
        );

        $this->templateAccessAnalyzer
            ->expects(self::once())
            ->method('analyzeTemplate')
            ->willReturn([$methodEntry]);

        $this->assertLoggerNotCalled();

        $result = $this->provider->getEntityFieldsUsedInEmailTemplates();

        self::assertEquals(
            [['entity' => $entityClass, 'field' => 'name']],
            $result
        );
    }

    public function testGetEntityFieldsUsedInEmailTemplatesLogsSyntaxErrorAndContinues(): void
    {
        $entityClass = \stdClass::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('broken_template');
        $emailTemplate->setEntityName($entityClass);
        $emailTemplate->setContent('{{ invalid twig %%');
        $emailTemplate->setSubject('');

        $this->repository
            ->expects(self::once())
            ->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([$emailTemplate]));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch');

        $e = new SyntaxError('Unexpected token');

        $this->templateAccessAnalyzer
            ->expects(self::once())
            ->method('analyzeTemplate')
            ->willThrowException($e);

        $this->loggerMock
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Found syntax error in email template "{template}": {message}',
                ['template' => 'broken_template', 'message' => $e->getMessage(), 'exception' => $e]
            );

        self::assertSame([], $this->provider->getEntityFieldsUsedInEmailTemplates());
    }

    public function testGetEntityFieldsUsedInEmailTemplatesUsesVariableTypesFromEvent(): void
    {
        $entityClass = \stdClass::class;
        $extraClass = \DateTime::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEntityName($entityClass);
        $emailTemplate->setContent('{{ entity.date }}');
        $emailTemplate->setSubject('');

        $this->repository
            ->expects(self::once())
            ->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([$emailTemplate]));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(Event::class))
            ->willReturnCallback(
                static function (Event $event): Event {
                    $event->setVariableTypes(
                        array_merge(
                            $event->getVariableTypes(),
                            ['extra' => \DateTime::class]
                        )
                    );

                    return $event;
                }
            );

        $entry = new TemplateAccessEntry(
            className: $extraClass,
            variableName: 'extra',
            attributeName: 'date',
            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            lineNumber: 1
        );

        $this->templateAccessAnalyzer
            ->expects(self::once())
            ->method('analyzeTemplate')
            ->with('{{ entity.date }}', ['entity' => $entityClass, 'extra' => $extraClass])
            ->willReturn([$entry]);

        $this->assertLoggerNotCalled();

        $result = $this->provider->getEntityFieldsUsedInEmailTemplates();

        self::assertEquals(
            [['entity' => $extraClass, 'field' => 'date']],
            $result
        );
    }

    public function testGetEntityFieldsUsedInEmailTemplatesWithNoEntityName(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEntityName(null);
        $emailTemplate->setContent('{{ someVar.field }}');
        $emailTemplate->setSubject('');

        $this->repository
            ->expects(self::once())
            ->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([$emailTemplate]));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(Event::class));

        $this->templateAccessAnalyzer
            ->expects(self::once())
            ->method('analyzeTemplate')
            ->with('{{ someVar.field }}', [])
            ->willReturn([]);

        $this->assertLoggerNotCalled();

        self::assertSame([], $this->provider->getEntityFieldsUsedInEmailTemplates());
    }

    public function testGetEntityFieldsUsedInEmailTemplatesAnalyzesTranslationContentWhenNotFallback(): void
    {
        $entityClass = \stdClass::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEntityName($entityClass);
        $emailTemplate->setContent('{{ entity.baseField }}');
        $emailTemplate->setSubject('');

        $translation = new EmailTemplateTranslation();
        $translation->setContent('{{ entity.translatedField }}');
        $translation->setContentFallback(false);
        $translation->setSubjectFallback(true); // subject uses base, content does not
        $emailTemplate->addTranslation($translation);

        $this->repository->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([$emailTemplate]));

        $this->eventDispatcher->method('dispatch')
            ->with(self::isInstanceOf(Event::class));

        $baseEntry = new TemplateAccessEntry(
            className: $entityClass,
            variableName: 'entity',
            attributeName: 'baseField',
            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            lineNumber: 1
        );
        $translationEntry = new TemplateAccessEntry(
            className: $entityClass,
            variableName: 'entity',
            attributeName: 'translatedField',
            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            lineNumber: 1
        );

        $this->templateAccessAnalyzer
            ->expects(self::exactly(2))
            ->method('analyzeTemplate')
            ->willReturnMap([
                ['{{ entity.baseField }}', ['entity' => $entityClass], [$baseEntry]],
                ['{{ entity.translatedField }}', ['entity' => $entityClass], [$translationEntry]],
            ]);

        $result = $this->provider->getEntityFieldsUsedInEmailTemplates();

        self::assertEquals(
            [
                ['entity' => $entityClass, 'field' => 'baseField'],
                ['entity' => $entityClass, 'field' => 'translatedField'],
            ],
            $result
        );
    }

    public function testGetEntityFieldsUsedInEmailTemplatesSkipsTranslationContentWhenFallback(): void
    {
        $entityClass = \stdClass::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEntityName($entityClass);
        $emailTemplate->setContent('{{ entity.baseField }}');
        $emailTemplate->setSubject('');

        // Translation with both content and subject as fallback — only base template is analyzed.
        $translation = new EmailTemplateTranslation();
        $translation->setContent(null);
        $translation->setContentFallback(true);
        $translation->setSubjectFallback(true);
        $emailTemplate->addTranslation($translation);

        $this->repository->method('getBatchIterator')
            ->willReturn(new \ArrayIterator([$emailTemplate]));

        $this->eventDispatcher->method('dispatch')
            ->with(self::isInstanceOf(Event::class));

        $baseEntry = new TemplateAccessEntry(
            className: $entityClass,
            variableName: 'entity',
            attributeName: 'baseField',
            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            lineNumber: 1
        );

        // Only the base template content is analyzed — the translation content is ignored.
        $this->templateAccessAnalyzer
            ->expects(self::once())
            ->method('analyzeTemplate')
            ->with('{{ entity.baseField }}', ['entity' => $entityClass])
            ->willReturn([$baseEntry]);

        $result = $this->provider->getEntityFieldsUsedInEmailTemplates();

        self::assertEquals(
            [['entity' => $entityClass, 'field' => 'baseField']],
            $result
        );
    }
}
