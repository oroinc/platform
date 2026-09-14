<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\SecurityBundle\ORM\DetectEntitiesWithoutOrganizationField;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsArticle;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class DetectEntitiesWithoutOrganizationFieldTest extends OrmTestCase
{
    private OwnershipMetadataProviderInterface&MockObject $ownershipMetadataProvider;
    private DetectEntitiesWithoutOrganizationField $detector;

    #[\Override]
    protected function setUp(): void
    {
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);

        $this->detector = new DetectEntitiesWithoutOrganizationField($this->ownershipMetadataProvider);
    }

    /**
     * @dataProvider ownershipMetadataProvider
     */
    public function testDetect(OwnershipMetadata $metadata, array $expectedEntityClasses): void
    {
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(CmsAddress::class)
            ->willReturn($metadata);

        $query = $this->createQuery('SELECT e.id FROM ' . CmsAddress::class . ' e');

        self::assertSame($expectedEntityClasses, $this->detector->detect($query));
    }

    public static function ownershipMetadataProvider(): array
    {
        return [
            'owned entity without a configured organization field is detected' => [
                new OwnershipMetadata('USER', 'owner', 'owner_id'),
                [CmsAddress::class],
            ],
            'owned entity with a configured organization field' => [
                new OwnershipMetadata('USER', 'owner', 'owner_id', 'organization', 'organization_id'),
                [],
            ],
            'entity without ownership' => [
                new OwnershipMetadata(),
                [],
            ],
            'organization-owned entity falls back to the owner field' => [
                new OwnershipMetadata('ORGANIZATION', 'organization', 'organization_id'),
                [],
            ],
        ];
    }

    public function testDetectInspectsAssociationJoins(): void
    {
        $this->configureMetadataByClass([
            CmsUser::class => self::ownedMetadataWithOrganization(),
            CmsArticle::class => self::ownedMetadataWithoutOrganization(),
        ]);

        $query = $this->createQuery('SELECT u.id FROM ' . CmsUser::class . ' u JOIN u.articles a');

        self::assertSame([CmsArticle::class], $this->detector->detect($query));
    }

    public function testDetectInspectsRangeJoins(): void
    {
        $this->configureMetadataByClass([
            CmsUser::class => self::ownedMetadataWithOrganization(),
            CmsAddress::class => self::ownedMetadataWithoutOrganization(),
        ]);

        $query = $this->createQuery(
            'SELECT u.id FROM ' . CmsUser::class . ' u JOIN ' . CmsAddress::class . ' a WITH a.user = u'
        );

        self::assertSame([CmsAddress::class], $this->detector->detect($query));
    }

    public function testDetectReportsEachEntityClassOnce(): void
    {
        $this->configureMetadataByClass([
            CmsUser::class => self::ownedMetadataWithoutOrganization(),
            CmsArticle::class => self::ownedMetadataWithoutOrganization(),
        ]);

        $query = $this->createQuery(
            'SELECT u.id FROM ' . CmsUser::class . ' u JOIN u.articles a JOIN a.user u2'
        );

        self::assertSame([CmsUser::class, CmsArticle::class], $this->detector->detect($query));
    }

    private static function ownedMetadataWithOrganization(): OwnershipMetadata
    {
        return new OwnershipMetadata('USER', 'owner', 'owner_id', 'organization', 'organization_id');
    }

    private static function ownedMetadataWithoutOrganization(): OwnershipMetadata
    {
        return new OwnershipMetadata('USER', 'owner', 'owner_id');
    }

    /**
     * @param array<string, OwnershipMetadata> $metadataByClass
     */
    private function configureMetadataByClass(array $metadataByClass): void
    {
        $this->ownershipMetadataProvider->method('getMetadata')
            ->willReturnCallback(static fn (?string $class) => $metadataByClass[$class]);
    }

    private function createQuery(string $dql): Query
    {
        return $this->getEntityManager()->createQuery($dql);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl(new AttributeDriver([]));

        return $em;
    }
}
