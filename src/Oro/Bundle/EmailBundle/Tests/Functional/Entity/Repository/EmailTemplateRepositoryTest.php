<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\NoResultException;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateData;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadLocalizedEmailTemplateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailTemplateRepositoryTest extends WebTestCase
{
    private const SYSTEM_TEMPLATE_EMAIL_WITHOUT_ENTITY = 'import_result';
    private const SYSTEM_TEMPLATE_EMAIL_WITH_ENTITY = 'user_reset_password';
    private const USER_ENTITY_TEMPLATE_NAME = 'user_reset_password';

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testFindByName(): void
    {
        $this->loadFixtures([LoadEmailTemplateData::class]);

        self::assertEquals(
            $this->getReference(LoadEmailTemplateData::NO_ENTITY_NAME_TEMPLATE_REFERENCE)->getId(),
            $this->getRepository()->findByName('no_entity_name')->getId()
        );
    }

    public function testFindByNameNotExistentEmailTemplate(): void
    {
        $this->loadFixtures([LoadEmailTemplateData::class]);

        self::assertNull($this->getRepository()->findByName('not_existent_template'));
    }

    public function testGetTemplateByEntityNameWithIncludeNonEntityAndIncludeSystemTemplates(): void
    {
        $this->loadFixtures([LoadEmailTemplateData::class]);

        /** @var User $owner */
        $owner = $this->getReference(LoadEmailTemplateData::OWNER_USER_REFERENCE);

        $actualResult = $this->getRepository()->getTemplateByEntityName(
            self::getContainer()->get('oro_security.acl_helper'),
            LoadEmailTemplateData::ENTITY_NAME,
            $owner->getOrganization(),
            true,
            true
        );

        $expectedIds = [
            $this->getMainEmailTemplateByName('datagrid_export_result')->getId(),
            $this->getMainEmailTemplateByName('export_result')->getId(),
            $this->getMainEmailTemplateByName('import_error')->getId(),
            $this->getMainEmailTemplateByName('import_result')->getId(),
            $this->getMainEmailTemplateByName('import_validation_result')->getId(),
            $this->getReference(LoadEmailTemplateData::NO_ENTITY_NAME_TEMPLATE_REFERENCE)->getId(),
            $this->getReference(LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE)->getId(),
            $this->getReference(LoadEmailTemplateData::NOT_SYSTEM_NO_ENTITY_TEMPLATE_REFERENCE)->getId(),
            $this->getMainEmailTemplateByName('sync_wrong_credentials_system_box')->getId(),
            $this->getMainEmailTemplateByName('sync_wrong_credentials_user_box')->getId(),
            $this->getReference(LoadEmailTemplateData::SYSTEM_WITH_ENTITY_TEMPLATE_REFERENCE)->getId(),
            $this->getReference(LoadEmailTemplateData::SYSTEM_FAIL_TO_COMPILE)->getId(),
            $this->getMainEmailTemplateByName('system_maintenance')->getId(),
        ];

        $actualIds = $this->getEntitiesIds($actualResult);
        foreach ($expectedIds as $emailTemplateId) {
            self::assertContains($emailTemplateId, $actualIds);
        }
    }

    /**
     * @dataProvider getTemplateByEntityNameDataProvider
     */
    public function testGetTemplateByEntityName(
        bool $includeNonEntity,
        bool $includeSystemTemplates,
        array $expectedReferences
    ): void {
        $this->loadFixtures([LoadEmailTemplateData::class]);

        /** @var User $owner */
        $owner = $this->getReference(LoadEmailTemplateData::OWNER_USER_REFERENCE);

        $actualResult = $this->getRepository()->getTemplateByEntityName(
            self::getContainer()->get('oro_security.acl_helper'),
            'Entity\Name',
            $owner->getOrganization(),
            $includeNonEntity,
            $includeSystemTemplates
        );

        self::assertEquals($this->getReferencesIds($expectedReferences), $this->getEntitiesIds($actualResult));
    }

    public function getTemplateByEntityNameDataProvider(): array
    {
        return [
            'with entity name only, not system templates only' => [
                'includeNonEntity' => false,
                'includeSystemTemplates' => false,
                'expectedEmailTemplates' => [
                    LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE
                ]
            ],
            'with and without entity name, not system templates only' => [
                'includeNonEntity' => true,
                'includeSystemTemplates' => false,
                'expectedEmailTemplates' => [
                    LoadEmailTemplateData::NO_ENTITY_NAME_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::NOT_SYSTEM_NO_ENTITY_TEMPLATE_REFERENCE
                ]
            ],
            'with entity name only, system and not system templates' => [
                'includeNonEntity' => false,
                'includeSystemTemplates' => true,
                'expectedEmailTemplates' => [
                    LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::SYSTEM_WITH_ENTITY_TEMPLATE_REFERENCE
                ]
            ]
        ];
    }

    /**
     * @dataProvider getEntityTemplatesDataProvider
     */
    public function testGetEntityTemplatesQueryBuilder(
        bool $includeNonEntity,
        bool $includeSystemTemplates,
        bool $visibleOnly,
        array $excludeTemplates,
        array $expectedReferences
    ): void {
        $this->loadFixtures([LoadEmailTemplateData::class]);

        /** @var User $owner */
        $owner = $this->getReference(LoadEmailTemplateData::OWNER_USER_REFERENCE);

        $actualResult = $this->getRepository()->getEntityTemplatesQueryBuilder(
            'Entity\Name',
            $owner->getOrganization(),
            $includeNonEntity,
            $includeSystemTemplates,
            $visibleOnly,
            array_map(
                function (string $reference) {
                    return $this->getReference($reference)->getName();
                },
                $excludeTemplates
            )
        )->getQuery()->getResult();

        self::assertEquals($this->getReferencesIds($expectedReferences), $this->getEntitiesIds($actualResult));
    }

    public function getEntityTemplatesDataProvider(): array
    {
        return [
            'with entity name only, not system templates only, include visible and not visible' => [
                'includeNonEntity' => false,
                'includeSystemTemplates' => false,
                'visibleOnly' => false,
                'excludeTemplates' => [],
                'expectedEmailTemplates' => [
                    LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::NOT_SYSTEM_NOT_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE
                ]
            ],
            'with and without entity name, not system templates only, include visible and not visible' => [
                'includeNonEntity' => true,
                'includeSystemTemplates' => false,
                'visibleOnly' => false,
                'excludeTemplates' => [],
                'expectedEmailTemplates' => [
                    LoadEmailTemplateData::NO_ENTITY_NAME_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::NOT_SYSTEM_NO_ENTITY_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::NOT_SYSTEM_NOT_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE
                ]
            ],
            'with entity name only, system and not system templates, include visible and not visible' => [
                'includeNonEntity' => false,
                'includeSystemTemplates' => true,
                'visibleOnly' => false,
                'excludeTemplates' => [],
                'expectedEmailTemplates' => [
                    LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::NOT_SYSTEM_NOT_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::SYSTEM_WITH_ENTITY_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::SYSTEM_NOT_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE
                ]
            ],
            'with entity name only, system and not system templates, include visible only' => [
                'includeNonEntity' => false,
                'includeSystemTemplates' => false,
                'visibleOnly' => true,
                'excludeTemplates' => [],
                'expectedEmailTemplates' => [
                    LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE
                ]
            ],
            'with entity name only, not system templates only, include visible and not visible, excluded' => [
                'includeNonEntity' => false,
                'includeSystemTemplates' => false,
                'visibleOnly' => false,
                'excludeTemplates' => [
                    LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE,
                ],
                'expectedEmailTemplates' => [
                    LoadEmailTemplateData::NOT_SYSTEM_NOT_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE
                ]
            ],
            'with and without entity name, not system templates only, include visible and not visible, excluded' => [
                'includeNonEntity' => true,
                'includeSystemTemplates' => false,
                'visibleOnly' => false,
                'excludeTemplates' => [
                    LoadEmailTemplateData::NO_ENTITY_NAME_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE,
                ],
                'expectedEmailTemplates' => [
                    LoadEmailTemplateData::NOT_SYSTEM_NO_ENTITY_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::NOT_SYSTEM_NOT_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE
                ]
            ],
            'with entity name only, system and not system templates, include visible and not visible, excluded' => [
                'includeNonEntity' => false,
                'includeSystemTemplates' => true,
                'visibleOnly' => false,
                'excludeTemplates' => [
                    LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::NOT_SYSTEM_NOT_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE,
                ],
                'expectedEmailTemplates' => [
                    LoadEmailTemplateData::SYSTEM_WITH_ENTITY_TEMPLATE_REFERENCE,
                    LoadEmailTemplateData::SYSTEM_NOT_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE
                ]
            ],
            'with entity name only, system and not system templates, include visible only, excluded' => [
                'includeNonEntity' => false,
                'includeSystemTemplates' => false,
                'visibleOnly' => true,
                'excludeTemplates' => [
                    LoadEmailTemplateData::NOT_SYSTEM_VISIBLE_WITH_ENTITY_TEMPLATE_REFERENCE
                ],
                'expectedEmailTemplates' => []
            ]
        ];
    }

    public function testGetDistinctByEntityNameQueryBuilderWithoutFilters(): void
    {
        $this->loadFixtures([LoadEmailTemplateData::class]);

        $actualResult = $this->getRepository()->getDistinctByEntityNameQueryBuilder()
            ->getQuery()
            ->getArrayResult();

        foreach ($actualResult as $row) {
            if (empty($row['entityName'])) {
                $this->fail('Failed asserting that there are no records with empty value in "entityName" column.');
            }
        }
    }

    public function testGetDistinctByEntityNameQueryBuilderWithFilterByName(): void
    {
        $this->loadFixtures([LoadEmailTemplateData::class]);

        $actualResult = $this->getRepository()->getDistinctByEntityNameQueryBuilder()
            ->andWhere('e.name IN (:names)')
            ->addOrderBy('e.entityName', 'asc')
            ->setParameter('names', [self::USER_ENTITY_TEMPLATE_NAME])
            ->getQuery()
            ->getArrayResult();

        $this->assertCount(1, $actualResult);
        $this->assertEquals(['entityName' => User::class], reset($actualResult));
    }

    public function testGetSystemTemplatesQueryBuilderWithSystemTemplateWithoutEntity(): void
    {
        $this->loadFixtures([LoadEmailTemplateData::class]);

        $actualEmailTemplates = $this->getRepository()->getSystemTemplatesQueryBuilder()
            ->andWhere('e.name = :templateName')
            ->setParameter('templateName', self::SYSTEM_TEMPLATE_EMAIL_WITHOUT_ENTITY)
            ->getQuery()
            ->getResult();

        self::assertNotEmpty($actualEmailTemplates);
    }

    public function testGetSystemTemplatesQueryBuilderWithSystemTemplateWithEntity(): void
    {
        $this->loadFixtures([LoadEmailTemplateData::class]);

        $actualEmailTemplates = $this->getRepository()->getSystemTemplatesQueryBuilder()
            ->andWhere('e.name = :templateName')
            ->setParameter('templateName', self::SYSTEM_TEMPLATE_EMAIL_WITH_ENTITY)
            ->getQuery()
            ->getResult();

        self::assertEmpty($actualEmailTemplates);
    }

    public function testGetSystemTemplatesQueryBuilderWithNotSystemTemplate(): void
    {
        $this->loadFixtures([LoadEmailTemplateData::class]);

        /** @var EmailTemplate $template */
        $template = $this->getReference(LoadEmailTemplateData::NOT_SYSTEM_TEMPLATE_REFERENCE);

        $actualEmailTemplates = $this->getRepository()->getSystemTemplatesQueryBuilder()
            ->andWhere('e.name = :templateName')
            ->setParameter('templateName', $template->getName())
            ->getQuery()
            ->getResult();

        self::assertEmpty($actualEmailTemplates);
    }

    public function testFindOneLocalizedWhenNoResult(): void
    {
        $this->expectException(NoResultException::class);

        $this->loadFixtures([LoadLocalizedEmailTemplateData::class]);

        self::assertNull($this->getRepository()->findWithLocalizations(
            new EmailTemplateCriteria('not_existing_template_name')
        ));
    }

    public function testFindOneLocalizedWithFrenchAsCurrentLanguage(): void
    {
        $this->loadFixtures([LoadLocalizedEmailTemplateData::class]);

        $emailTemplate = $this->getRepository()->findWithLocalizations(
            new EmailTemplateCriteria('french_localized_template', User::class)
        );

        self::assertEquals(LoadLocalizedEmailTemplateData::FRENCH_LOCALIZED_SUBJECT, $emailTemplate->getSubject());
        self::assertEquals(LoadLocalizedEmailTemplateData::FRENCH_LOCALIZED_CONTENT, $emailTemplate->getContent());
    }

    public function testFindOneLocalizedWhenNoEntityName(): void
    {
        $this->loadFixtures([LoadLocalizedEmailTemplateData::class]);

        $emailTemplate = $this->getRepository()->findWithLocalizations(
            new EmailTemplateCriteria('no_entity_localized_template', null)
        );

        self::assertEquals(LoadLocalizedEmailTemplateData::FRENCH_LOCALIZED_SUBJECT, $emailTemplate->getSubject());
        self::assertEquals(LoadLocalizedEmailTemplateData::FRENCH_LOCALIZED_CONTENT, $emailTemplate->getContent());
    }

    public function testIsExistFalse()
    {
        $this->loadFixtures([LoadEmailTemplateData::class]);
        $expectedResult = $this->getRepository()->isExist(new EmailTemplateCriteria('some_unexisted'));

        self::assertFalse($expectedResult);
    }

    public function testIsExist()
    {
        $this->loadFixtures([LoadEmailTemplateData::class]);
        $expectedResult = $this->getRepository()->isExist(new EmailTemplateCriteria('test_template'));

        self::assertTrue($expectedResult);
    }

    private function getEntitiesIds(array $entities): array
    {
        return array_map(function ($entity) {
            return $entity->getId();
        }, $entities);
    }

    private function getReferencesIds(array $references): array
    {
        return array_map(function ($reference) {
            return $this->getReference($reference)->getId();
        }, $references);
    }

    private function getMainEmailTemplateByName(string $name): EmailTemplate
    {
        return $this->getRepository()->findOneBy(['name' => $name]);
    }

    private function getRepository(): EmailTemplateRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(EmailTemplate::class);
    }
}
