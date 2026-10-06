<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;
use Extend\Entity\TestEntity1;
use Extend\Entity\TestEntity2;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture\LoadExtendedRelationsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class EntitiesControllerTest extends WebTestCase
{
    private const ENTITY_NAME = 'Extend_Entity_TestEntity1';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadExtendedRelationsData::class]);
    }

    /**
     * @dataProvider relationsProvider
     */
    public function testRelationAction($fieldName)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_entity_relation', [
                'id' => $this->getTestEntity1()->getId(),
                'entityName' => self::ENTITY_NAME,
                'fieldName' => $fieldName,
            ])
        );
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($response, 200);
    }

    public function relationsProvider(): array
    {
        return [
            'unidirectional many-to-many'                => ['uniM2MNDTargets'],
            'bidirectional many-to-many'                 => ['biM2MTargets'],
            'bidirectional many-to-many without default' => ['biM2MNDTargets'],
            'unidirectional one-to-many'                 => ['uniO2MTargets'],
            'unidirectional one-to-many without default' => ['uniO2MNDTargets'],
            'bidirectional one-to-many'                  => ['biO2MNDTargets']
        ];
    }

    public function testViewAction(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_entity_view', [
                'entityName' => self::ENTITY_NAME,
                'id' => $this->getTestEntity1()->getId(),
            ])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testViewActionForNotExistingRecord(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_entity_view', [
                'entityName' => self::ENTITY_NAME,
                'id' => $this->getNotExistingId(TestEntity1::class),
            ])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testUpdateAction(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_entity_update', [
                'entityName' => self::ENTITY_NAME,
                'id' => $this->getTestEntity1()->getId(),
            ])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testDetailedAction(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_entity_detailed', [
                'id' => $this->getTestEntity2()->getId(),
                'entityName' => self::ENTITY_NAME,
                'fieldName' => 'uniM2MNDTargets',
            ])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testDetailedActionForNotExistingRecord(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_entity_detailed', [
                'id' => $this->getNotExistingId(TestEntity2::class),
                'entityName' => self::ENTITY_NAME,
                'fieldName' => 'uniM2MNDTargets',
            ])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    private function getTestEntity1(): TestEntity1
    {
        return $this->getFirstRecord(TestEntity1::class);
    }

    private function getTestEntity2(): TestEntity2
    {
        return $this->getFirstRecord(TestEntity2::class);
    }

    private function getFirstRecord(string $entityClass): object
    {
        return $this->getRepository($entityClass)
            ->createQueryBuilder('e')
            ->orderBy('e.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    private function getNotExistingId(string $entityClass): int
    {
        $maxId = $this->getRepository($entityClass)
            ->createQueryBuilder('e')
            ->select('MAX(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$maxId + 1;
    }

    private function getRepository(string $entityClass): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository($entityClass);
    }
}
