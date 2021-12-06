<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Entity\Repository\AuditFieldRepository;
use Oro\Bundle\DataAuditBundle\Model\EntityReference;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class AuditFieldRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function getRepository(): AuditFieldRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(AuditField::class);
    }

    private function getAuditRepository(): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Audit::class);
    }

    private function getEntityChangesToAuditEntryConverter(): EntityChangesToAuditEntryConverter
    {
        return self::getContainer()->get('oro_dataaudit.converter.entity_changes_to_audit_entry');
    }

    private function getAdminUser(): User
    {
        return self::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => self::AUTH_USER]);
    }

    public function testEmptyIds()
    {
        $this->assertSame(
            [],
            $this->getRepository()->getVisibleFieldsByAuditIds([])
        );
    }

    public function testGetVisibleFieldsByAuditIds()
    {
        $audits = $this->getAuditRepository()->findAll();
        $this->assertEmpty($audits);

        $this->getEntityChangesToAuditEntryConverter()->convert(
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

        $audits = $this->getAuditRepository()->findAll();
        $this->assertIsArray($audits);
        $this->assertNotEmpty($audits);
        $this->assertCount(1, $audits);

        $audit = $this->getAuditRepository()->findOneBy([]);
        $this->assertInstanceOf(Audit::class, $audit);

        $fieldsByAudit = $this->getRepository()->getVisibleFieldsByAuditIds([$audit->getId()]);
        $this->assertIsArray($fieldsByAudit);
        $this->assertNotEmpty($fieldsByAudit);
        $this->assertCount(1, $fieldsByAudit);

        $fields = reset($fieldsByAudit);
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        $this->assertCount(1, $fields);

        $field = reset($fields);
        $this->assertInstanceOf(AuditField::class, $field);

        /** @var AuditField $field */
        $this->assertTrue($field->isVisible());
    }

    public function testGetVisibleFieldsByAuditIdsAndSkipInvisible()
    {
        $audits = $this->getAuditRepository()->findAll();
        $this->assertEmpty($audits);

        $this->getEntityChangesToAuditEntryConverter()->convert(
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

        $audits = $this->getAuditRepository()->findAll();
        $this->assertIsArray($audits);
        $this->assertNotEmpty($audits);
        $this->assertCount(1, $audits);

        $audit = $this->getAuditRepository()->findOneBy([]);
        $this->assertInstanceOf(Audit::class, $audit);

        $this->getRepository()
            ->createQueryBuilder('f')
            ->update()
            ->set('f.visible', ':visible')
            ->setParameter('visible', false)
            ->getQuery()
            ->execute();

        $fieldsByAudit = $this->getRepository()->getVisibleFieldsByAuditIds([$audit->getId()]);
        $this->assertIsArray($fieldsByAudit);
        $this->assertEmpty($fieldsByAudit);
    }
}
