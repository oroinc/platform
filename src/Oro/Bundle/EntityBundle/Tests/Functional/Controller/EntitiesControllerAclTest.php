<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadDifferentOwnerEntitiesData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityWithUserOwnership;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class EntitiesControllerAclTest extends WebTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadDifferentOwnerEntitiesData::class]);
    }

    public function testViewOwnRecord(): void
    {
        $this->givenAccessLevel(AccessLevel::BASIC_LEVEL);

        $this->client->request('GET', $this->getViewUrl($this->getOwnRecordId()));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testViewRecordOfAnotherOwner(): void
    {
        $this->givenAccessLevel(AccessLevel::BASIC_LEVEL);

        $this->client->request('GET', $this->getViewUrl($this->getSameBusinessUnitRecordId()));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testViewNotExistingRecord(): void
    {
        $this->givenAccessLevel(AccessLevel::BASIC_LEVEL);

        $this->client->request('GET', $this->getViewUrl($this->getNotExistingRecordId()));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testViewWhenAccessToEntityIsDenied(): void
    {
        $this->updateRolePermission(
            'ROLE_ADMINISTRATOR',
            TestEntityWithUserOwnership::class,
            AccessLevel::NONE_LEVEL,
            'VIEW'
        );

        $this->client->request('GET', $this->getViewUrl($this->getOwnRecordId()));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testUpdateOwnRecord(): void
    {
        $this->givenAccessLevel(AccessLevel::BASIC_LEVEL);

        $this->client->request('GET', $this->getUpdateUrl($this->getOwnRecordId()));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testUpdateRecordOfAnotherOwner(): void
    {
        $this->givenAccessLevel(AccessLevel::BASIC_LEVEL);

        $this->client->request('GET', $this->getUpdateUrl($this->getSameBusinessUnitRecordId()));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testSubmitUpdateFormForRecordOfAnotherOwner(): void
    {
        $this->givenAccessLevel(AccessLevel::BASIC_LEVEL);
        $recordId = $this->getSameBusinessUnitRecordId();

        $this->client->request(
            'POST',
            $this->getUpdateUrl($recordId),
            ['custom_entity_type' => ['name' => 'updated by another user']]
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
        self::assertEquals(
            LoadDifferentOwnerEntitiesData::SAME_BUSINESS_UNIT_OWNED_ENTITY,
            $this->findRecordName($recordId)
        );
    }

    public function testUpdateNotExistingRecord(): void
    {
        $this->givenAccessLevel(AccessLevel::BASIC_LEVEL);

        $this->client->request('GET', $this->getUpdateUrl($this->getNotExistingRecordId()));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testCreateNewRecord(): void
    {
        $this->givenAccessLevel(AccessLevel::BASIC_LEVEL);

        $this->client->request(
            'GET',
            $this->getUrl('oro_entity_update', ['entityName' => $this->getEntityName()])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testDeleteOwnRecord(): void
    {
        $this->givenAccessLevel(AccessLevel::BASIC_LEVEL);
        $recordId = $this->getOwnRecordId();

        $this->sendDeleteRequest($recordId);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
        self::assertNull($this->findRecordName($recordId));
    }

    public function testDeleteRecordOfAnotherOwner(): void
    {
        $this->givenAccessLevel(AccessLevel::BASIC_LEVEL);
        $recordId = $this->getSameBusinessUnitRecordId();

        $this->sendDeleteRequest($recordId);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
        self::assertEquals(
            LoadDifferentOwnerEntitiesData::SAME_BUSINESS_UNIT_OWNED_ENTITY,
            $this->findRecordName($recordId)
        );
    }

    public function testViewOwnRecordAtBusinessUnitAccessLevel(): void
    {
        $this->givenAccessLevel(AccessLevel::LOCAL_LEVEL);

        $this->client->request('GET', $this->getViewUrl($this->getOwnRecordId()));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testViewRecordOfSameBusinessUnitAtBusinessUnitAccessLevel(): void
    {
        $this->givenAccessLevel(AccessLevel::LOCAL_LEVEL);

        $this->client->request('GET', $this->getViewUrl($this->getSameBusinessUnitRecordId()));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testViewRecordOfAnotherBusinessUnitAtBusinessUnitAccessLevel(): void
    {
        $this->givenAccessLevel(AccessLevel::LOCAL_LEVEL);

        $this->client->request('GET', $this->getViewUrl($this->getAnotherBusinessUnitRecordId()));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testUpdateRecordOfAnotherBusinessUnitAtBusinessUnitAccessLevel(): void
    {
        $this->givenAccessLevel(AccessLevel::LOCAL_LEVEL);

        $this->client->request('GET', $this->getUpdateUrl($this->getAnotherBusinessUnitRecordId()));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testDeleteRecordOfAnotherBusinessUnitAtBusinessUnitAccessLevel(): void
    {
        $this->givenAccessLevel(AccessLevel::LOCAL_LEVEL);
        $recordId = $this->getAnotherBusinessUnitRecordId();

        $this->sendDeleteRequest($recordId);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
        self::assertEquals(
            LoadDifferentOwnerEntitiesData::ANOTHER_BUSINESS_UNIT_OWNED_ENTITY,
            $this->findRecordName($recordId)
        );
    }

    private function givenAccessLevel(int $accessLevel): void
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            TestEntityWithUserOwnership::class,
            [
                'VIEW' => $accessLevel,
                'CREATE' => $accessLevel,
                'EDIT' => $accessLevel,
                'DELETE' => $accessLevel
            ]
        );
    }

    private function sendDeleteRequest(int $recordId): void
    {
        $this->ajaxRequest(
            'DELETE',
            $this->getUrl('oro_entity_delete', ['entityName' => $this->getEntityName(), 'id' => $recordId])
        );
    }

    private function getViewUrl(int $recordId): string
    {
        return $this->getUrl('oro_entity_view', ['entityName' => $this->getEntityName(), 'id' => $recordId]);
    }

    private function getUpdateUrl(int $recordId): string
    {
        return $this->getUrl('oro_entity_update', ['entityName' => $this->getEntityName(), 'id' => $recordId]);
    }

    private function getEntityName(): string
    {
        return str_replace('\\', '_', TestEntityWithUserOwnership::class);
    }

    private function getOwnRecordId(): int
    {
        return $this->getRecordId(LoadDifferentOwnerEntitiesData::ADMIN_OWNED_ENTITY);
    }

    private function getSameBusinessUnitRecordId(): int
    {
        return $this->getRecordId(LoadDifferentOwnerEntitiesData::SAME_BUSINESS_UNIT_OWNED_ENTITY);
    }

    private function getAnotherBusinessUnitRecordId(): int
    {
        return $this->getRecordId(LoadDifferentOwnerEntitiesData::ANOTHER_BUSINESS_UNIT_OWNED_ENTITY);
    }

    private function getRecordId(string $reference): int
    {
        /** @var TestEntityWithUserOwnership $record */
        $record = $this->getReference($reference);

        return $record->getId();
    }

    private function getNotExistingRecordId(): int
    {
        $maxId = $this->getEntityManager()->createQueryBuilder()
            ->select('MAX(e.id)')
            ->from(TestEntityWithUserOwnership::class, 'e')
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$maxId + 1;
    }

    private function findRecordName(int $recordId): ?string
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('e.name')
            ->from(TestEntityWithUserOwnership::class, 'e')
            ->where('e.id = :id')
            ->setParameter('id', $recordId)
            ->getQuery()
            ->getArrayResult();

        return $rows ? $rows[0]['name'] : null;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(TestEntityWithUserOwnership::class);
    }
}
