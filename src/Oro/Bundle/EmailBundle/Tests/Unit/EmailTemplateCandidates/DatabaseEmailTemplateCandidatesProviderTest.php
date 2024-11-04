<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\EmailTemplateCandidates;

use Oro\Bundle\EmailBundle\EmailTemplateCandidates\DatabaseEmailTemplateCandidatesProvider;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatabaseEmailTemplateCandidatesProviderTest extends TestCase
{
    private DoctrineHelper|MockObject $doctrineHelper;

    private DatabaseEmailTemplateCandidatesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->provider = new DatabaseEmailTemplateCandidatesProvider($this->doctrineHelper);
    }

    public function testShouldReturnEmptyArrayWhenStartsWithAt(): void
    {
        self::assertEmpty(
            $this->provider->getCandidatesNames(new EmailTemplateCriteria('@sample_namespace/sample_name'))
        );
    }

    public function testWhenEmptyContext(): void
    {
        self::assertEquals(
            ['@db:/sample_name'],
            $this->provider->getCandidatesNames(new EmailTemplateCriteria('sample_name'))
        );
    }

    public function testWhenContextWithLocalizationEntity(): void
    {
        $localization = new LocalizationStub(42);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isManageableEntity')
            ->with($localization)
            ->willReturn(true);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($localization)
            ->willReturn(42);

        self::assertEquals(
            ['@db:localization=42/sample_name'],
            $this->provider->getCandidatesNames(
                new EmailTemplateCriteria('sample_name'),
                ['localization' => $localization]
            )
        );
    }

    public function testWhenContextWithNonManageableEntity(): void
    {
        $object = new \stdClass();

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isManageableEntity')
            ->with($object)
            ->willReturn(false);

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getSingleEntityIdentifier');

        self::assertEquals(
            ['@db:/sample_name'],
            $this->provider->getCandidatesNames(new EmailTemplateCriteria('sample_name'), ['sample_key' => $object])
        );
    }

    public function testWhenContextWithLocalizationId(): void
    {
        $localization = 42;

        $this->doctrineHelper
            ->expects(self::never())
            ->method('isManageableEntity');

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getSingleEntityIdentifier');

        self::assertEquals(
            ['@db:localization=42/sample_name'],
            $this->provider->getCandidatesNames(
                new EmailTemplateCriteria('sample_name'),
                ['localization' => $localization]
            )
        );
    }
}
