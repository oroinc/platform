<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Converter;

use Oro\Bundle\ImportExportBundle\Converter\RelationCalculator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class RelationCalculatorTest extends WebTestCase
{
    /**
     * @var RelationCalculator
     */
    protected $relationCalculator;

    protected function setUp()
    {
        $this->initClient();
        $this->relationCalculator = $this->getContainer()->get('oro_importexport.data_converter.relation_calculator');
    }

    public function testGetMaxRelatedEntities()
    {
        $entityName = 'Oro\Bundle\UserBundle\Entity\User';
        $maxGroups = 0;
        $maxRoles = 0;
        $maxBusinessUnits = 0;

        // calculate expected data
        /** @var User[] $users */
        $users = $this->getContainer()->get('doctrine')->getRepository($entityName)->findAll();
        foreach ($users as $user) {
            $groupsCount = count($user->getGroups());
            if ($groupsCount > $maxGroups) {
                $maxGroups = $groupsCount;
            }
            $rolesCount = count($user->getRoles());
            if ($rolesCount > $maxRoles) {
                $maxRoles = $rolesCount;
            }
            $businessUnitsCount = count($user->getBusinessUnits());
            if ($businessUnitsCount > $maxBusinessUnits) {
                $maxBusinessUnits = $businessUnitsCount;
            }
        }

        // assert test data
        $this->assertEquals($maxGroups, $this->relationCalculator->getMaxRelatedEntities($entityName, 'groups'));
        $this->assertEquals($maxRoles, $this->relationCalculator->getMaxRelatedEntities($entityName, 'roles'));
        $this->assertEquals(
            $maxBusinessUnits,
            $this->relationCalculator->getMaxRelatedEntities($entityName, 'businessUnits')
        );
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Oro\Bundle\UserBundle\Entity\User:username is not multiple relation field
     */
    public function testGetMaxRelatedEntitiesException()
    {
        $this->relationCalculator->getMaxRelatedEntities('Oro\Bundle\UserBundle\Entity\User', 'username');
    }
}
