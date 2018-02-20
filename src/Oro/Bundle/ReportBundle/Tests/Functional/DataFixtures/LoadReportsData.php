<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\ReportType;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

class LoadReportsData extends AbstractFixture
{
    const REPORTS = [
        [
            'name' => 'Report 1',
            'description' => 'First rest report',
            'entity' => User::class,
            'definition' => '{"columns":[{"name":"id","label":"Id","func":"","sorting":""}]}'
        ],
        [
            'name' => 'Report 2',
            'description' => 'Second test report',
            'entity' => Role::class,
            'definition' => '{"columns":[{"name":"id","label":"Id","func":"","sorting":""}]}'
        ],
        [
            'name' => 'Report 3',
            'description' => 'Third test report',
            'entity' => Group::class,
            'definition' => '{"columns":[{"name":"id","label":"Id","func":"","sorting":""}]}'
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var BusinessUnit $owner */
        $owner = $manager->getRepository(BusinessUnit::class)
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT]);

        /** @var ReportType $type */
        $type = $manager->getRepository(ReportType::class)->findOneBy(['name' => ReportType::TYPE_TABLE]);

        foreach (self::REPORTS as $values) {
            $report = new Report();
            $report->setName($values['name']);
            $report->setDescription($values['description']);
            $report->setEntity($values['entity']);
            $report->setType($type);
            $report->setDefinition($values['definition']);
            $report->setOwner($owner);
            $report->setOrganization($owner->getOrganization());
            $manager->persist($report);
        }
        $manager->flush();
    }
}
