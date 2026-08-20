<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\EntityAuditFieldSearchProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityAuditFieldSearchProviderTest extends TestCase
{
    private const string USER = 'Oro\Bundle\UserBundle\Entity\User';
    private const string CONTACT = 'Oro\Bundle\ContactBundle\Entity\Contact';

    private AuditConfigProvider&MockObject $auditConfigProvider;
    private ConfigManager&MockObject $entityConfigManager;
    private EntityAuditFieldSearchProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->auditConfigProvider = $this->createMock(AuditConfigProvider::class);
        $this->entityConfigManager = $this->createMock(ConfigManager::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'oro.user.email.label' => 'Primary Email',
                'oro.user.username.label' => 'Username',
                'oro.contact.email.label' => 'Email',
                default => $key,
            });

        $this->provider = new EntityAuditFieldSearchProvider(
            $this->auditConfigProvider,
            $this->entityConfigManager,
            $translator
        );
    }

    public function testReturnsNothingForAnEmptyTerm(): void
    {
        $this->auditConfigProvider->expects(self::never())
            ->method('getAllAuditableEntities');

        self::assertSame(['classes' => [], 'fields' => []], $this->provider->getMatchingFields('  '));
    }

    public function testMatchesTheLabelShownInTheGrid(): void
    {
        $this->givenAuditableFields();

        // "Primary Email" is the label of User::email; Contact::email is labelled just "Email".
        self::assertSame(
            ['classes' => [self::USER], 'fields' => ['email']],
            $this->provider->getMatchingFields('Primary Email')
        );
    }

    public function testMatchesCaseInsensitivelyAndCollectsEveryMatchingClass(): void
    {
        $this->givenAuditableFields();

        self::assertSame(
            ['classes' => [self::USER, self::CONTACT], 'fields' => ['email']],
            $this->provider->getMatchingFields('eMAIl')
        );
    }

    public function testIgnoresFieldsThatAreNotAudited(): void
    {
        $this->givenAuditableFields();

        // User::createdAt has a matching label but is not an audited field.
        self::assertSame(['classes' => [], 'fields' => []], $this->provider->getMatchingFields('Created At'));
    }

    public function testDoesNotMatchAnythingWhenTheTermIsUnknown(): void
    {
        $this->givenAuditableFields();

        self::assertSame(['classes' => [], 'fields' => []], $this->provider->getMatchingFields('nothing here'));
    }

    private function givenAuditableFields(): void
    {
        $this->auditConfigProvider->expects(self::any())
            ->method('getAllAuditableEntities')
            ->willReturn([self::USER, self::CONTACT, 'Some\Entity\WithoutAuditedFields']);
        $this->auditConfigProvider->expects(self::any())
            ->method('getAuditableFields')
            ->willReturnCallback(static fn (string $class): array => match ($class) {
                self::USER => ['email', 'username'],
                self::CONTACT => ['email'],
                default => [],
            });

        $this->entityConfigManager->expects(self::any())
            ->method('getConfigs')
            ->willReturnCallback(fn (string $scope, ?string $class): array => match ($class) {
                self::USER => [
                    $this->fieldConfig(self::USER, 'email', 'oro.user.email.label'),
                    $this->fieldConfig(self::USER, 'username', 'oro.user.username.label'),
                    $this->fieldConfig(self::USER, 'createdAt', 'Created At'),
                    // an entity-level config in the same scope must be skipped
                    new Config(new EntityConfigId($scope, self::USER), ['label' => 'Primary Email']),
                ],
                self::CONTACT => [$this->fieldConfig(self::CONTACT, 'email', 'oro.contact.email.label')],
                default => [],
            });
    }

    private function fieldConfig(string $class, string $field, string $label): Config
    {
        return new Config(new FieldConfigId('entity', $class, $field, 'string'), ['label' => $label]);
    }
}
