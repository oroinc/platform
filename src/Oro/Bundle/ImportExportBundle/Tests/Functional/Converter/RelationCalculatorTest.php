<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Converter;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculator;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class RelationCalculatorTest extends WebTestCase
{
    /** @var RelationCalculator */
    private $relationCalculator;

    protected function setUp(): void
    {
        $this->initClient();
        $this->relationCalculator = $this->getContainer()->get('oro_importexport.data_converter.relation_calculator');
    }

    public function testGetMaxRelatedEntities()
    {
        $maxGroups = 0;
        $maxRoles = 0;
        $maxBusinessUnits = 0;

        // calculate expected data
        /** @var EntityRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(User::class);
        /** @var User[] $users */
        $users = $repo->findAll();
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
        $this->assertEquals($maxGroups, $this->relationCalculator->getMaxRelatedEntities(User::class, 'groups'));
        $this->assertEquals($maxRoles, $this->relationCalculator->getMaxRelatedEntities(User::class, 'userRoles'));
        $this->assertEquals(
            $maxBusinessUnits,
            $this->relationCalculator->getMaxRelatedEntities(User::class, 'businessUnits')
        );
    }

    public function testGetMaxRelatedEntitiesException()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Oro\Bundle\UserBundle\Entity\User:username is not multiple relation field');

        $this->relationCalculator->getMaxRelatedEntities(User::class, 'username');
    }
}
