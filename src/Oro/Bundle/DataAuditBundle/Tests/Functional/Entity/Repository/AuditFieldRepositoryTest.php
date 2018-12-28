<?php

namespace Oro\Bundle\DataAuditBundle\Entity\Repository;

use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Model\EntityReference;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\Helper\AdminUserTrait;

/**
 * @dbIsolationPerTest
 */
class AuditFieldRepositoryTest extends WebTestCase
{
    use AdminUserTrait;

    /** @var AuditFieldRepository */
    private $repository;

    /** @var AuditRepository */
    private $auditRepository;

    /** @var EntityChangesToAuditEntryConverter */
    private $entityChangesToAuditEntryConverter;

    protected function setUp()
    {
        $this->initClient();
        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(AuditField::class)->getRepository(AuditField::class);
        $this->auditRepository = $this->getContainer()->get('doctrine')->getRepository(Audit::class);
        $this->entityChangesToAuditEntryConverter = $this->getContainer()->get(
            'oro_dataaudit.converter.entity_changes_to_audit_entry'
        );
    }

    public function testEmptyIds()
    {
        $this->assertSame(
            [],
            $this->repository->getVisibleFieldsByAuditIds([])
        );
    }

    public function testGetVisibleFieldsByAuditIds()
    {
        $audits = $this->auditRepository->findAll();
        $this->assertEmpty($audits);

        $this->entityChangesToAuditEntryConverter->convert(
            [
                [
                    'entity_class' => get_class($this->getAdminUser()),
                    'entity_id' => $this->getAdminUser()->getId(),
                    'change_set' => ['namePrefix' => [null, 'MR']],
                ],
            ],
            UUIDGenerator::v4(),
            new \DateTime(),
            new EntityReference(get_class($this->getAdminUser()), $this->getAdminUser()->getId()),
            new EntityReference(
                get_class($this->getAdminUser()->getOrganization()),
                $this->getAdminUser()->getOrganization()->getId()
            ),
            new EntityReference()
        );

        $audits = $this->auditRepository->findAll();
        $this->assertInternalType('array', $audits);
        $this->assertNotEmpty($audits);
        $this->assertCount(1, $audits);

        $audit = $this->auditRepository->findOneBy([]);
        $this->assertInstanceOf(Audit::class, $audit);

        $fieldsByAudit = $this->repository->getVisibleFieldsByAuditIds([$audit->getId()]);
        $this->assertInternalType('array', $fieldsByAudit);
        $this->assertNotEmpty($fieldsByAudit);
        $this->assertCount(1, $fieldsByAudit);

        $fields = reset($fieldsByAudit);
        $this->assertInternalType('array', $fields);
        $this->assertNotEmpty($fields);
        $this->assertCount(1, $fields);

        $field = reset($fields);
        $this->assertInstanceOf(AuditField::class, $field);

        /** @var AuditField $field */
        $this->assertTrue($field->isVisible());
    }

    public function testGetVisibleFieldsByAuditIdsAndSkipInvisible()
    {
        $audits = $this->auditRepository->findAll();
        $this->assertEmpty($audits);

        $this->entityChangesToAuditEntryConverter->convert(
            [
                [
                    'entity_class' => get_class($this->getAdminUser()),
                    'entity_id' => $this->getAdminUser()->getId(),
                    'change_set' => ['namePrefix' => [null, 'MR']],
                ],
            ],
            UUIDGenerator::v4(),
            new \DateTime(),
            new EntityReference(get_class($this->getAdminUser()), $this->getAdminUser()->getId()),
            new EntityReference(
                get_class($this->getAdminUser()->getOrganization()),
                $this->getAdminUser()->getOrganization()->getId()
            ),
            new EntityReference()
        );

        $audits = $this->auditRepository->findAll();
        $this->assertInternalType('array', $audits);
        $this->assertNotEmpty($audits);
        $this->assertCount(1, $audits);

        $audit = $this->auditRepository->findOneBy([]);
        $this->assertInstanceOf(Audit::class, $audit);

        $this->repository
            ->createQueryBuilder('f')
            ->update()
            ->set('f.visible', ':visible')
            ->setParameter('visible', false)
            ->getQuery()
            ->execute();

        $fieldsByAudit = $this->repository->getVisibleFieldsByAuditIds([$audit->getId()]);
        $this->assertInternalType('array', $fieldsByAudit);
        $this->assertEmpty($fieldsByAudit);
    }
}
