<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class AbstractCommonEntityOwnershipDecisionMakerTest extends \PHPUnit\Framework\TestCase
{
    protected const ORG_ID = 10;
    protected const BU_ID = 100;
    protected const USER_ID = 10000;

    protected OwnerTree $tree;

    protected Organization $org1;
    protected Organization $org2;
    protected Organization $org3;
    protected Organization $org4;

    protected BusinessUnit $bu1;
    protected BusinessUnit $bu2;
    protected BusinessUnit $bu3;
    protected BusinessUnit $bu31;
    protected BusinessUnit $bu4;
    protected BusinessUnit $bu41;
    protected BusinessUnit $bu411;

    protected User $user1;
    protected User $user2;
    protected User $user3;
    protected User $user31;
    protected User $user4;
    protected User $user411;

    protected function buildTestTree()
    {
        /**
         * org1  org2     org3         org4
         *                |            |
         *  bu1   bu2     +-bu3        +-bu4
         *        |       | |            |
         *        |       | +-bu31       |
         *        |       | | |          |
         *        |       | | +-user31   |
         *        |       | |            |
         *  user1 +-user2 | +-user3      +-user4
         *                |                |
         *                +-bu32           +-bu3
         *                  |              +-bu4
         *                  +-bu321          |
         *                                   +-bu41
         *                                     |
         *                                     +-bu411
         *                                       |
         *                                       +-user411
         *
         * user1 user2 user3 user31 user4 user411
         *
         * org1  org2  org3  org3   org4  org4
         * org2        org2
         *
         * bu1   bu2   bu3   bu31   bu4   bu411
         * bu2         bu2
         */
        $this->org1 = new Organization(self::ORG_ID + 1);
        $this->org2 = new Organization(self::ORG_ID + 2);
        $this->org3 = new Organization(self::ORG_ID + 3);
        $this->org4 = new Organization(self::ORG_ID + 4);

        $this->bu1 = new BusinessUnit(self::BU_ID + 1);
        $this->bu2 = new BusinessUnit(self::BU_ID + 2);
        $this->bu3 = new BusinessUnit(self::BU_ID + 3);
        $this->bu31 = new BusinessUnit(self::BU_ID + 31, $this->bu3);
        $this->bu4 = new BusinessUnit(self::BU_ID + 4);
        $this->bu41 = new BusinessUnit(self::BU_ID + 41, $this->bu4);
        $this->bu411 = new BusinessUnit(self::BU_ID + 411, $this->bu41);

        $this->user1 = new User(self::USER_ID + 1, null, $this->org1);
        $this->user2 = new User(self::USER_ID + 2, $this->bu2, $this->org2);
        $this->user3 = new User(self::USER_ID + 3, $this->bu3, $this->org3);
        $this->user31 = new User(self::USER_ID + 31, $this->bu31, $this->org3);
        $this->user4 = new User(self::USER_ID + 4, $this->bu4, $this->org4);
        $this->user411 = new User(self::USER_ID + 411, $this->bu411, $this->org4);

        $this->tree->addBusinessUnit(self::BU_ID + 1, null);
        $this->tree->addBusinessUnit(self::BU_ID + 2, null);
        $this->tree->addBusinessUnit(self::BU_ID + 3, self::ORG_ID + 3);
        $this->tree->addBusinessUnit(self::BU_ID + 31, self::ORG_ID + 3);
        $this->tree->addBusinessUnit(self::BU_ID + 32, self::ORG_ID + 3);
        $this->tree->addBusinessUnit(self::BU_ID + 321, self::ORG_ID + 3);
        $this->tree->addBusinessUnit(self::BU_ID + 4, self::ORG_ID + 4);
        $this->tree->addBusinessUnit(self::BU_ID + 41, self::ORG_ID + 4);
        $this->tree->addBusinessUnit(self::BU_ID + 411, self::ORG_ID + 4);

        $this->buildTree();

        $this->tree->addUser(self::USER_ID + 1, null);
        $this->tree->addUser(self::USER_ID + 2, self::BU_ID + 2);
        $this->tree->addUser(self::USER_ID + 3, self::BU_ID + 3);
        $this->tree->addUser(self::USER_ID + 31, self::BU_ID + 31);
        $this->tree->addUser(self::USER_ID + 4, self::BU_ID + 4);
        $this->tree->addUser(self::USER_ID + 41, self::BU_ID + 41);
        $this->tree->addUser(self::USER_ID + 411, self::BU_ID + 411);

        $this->tree->addUserOrganization(self::USER_ID + 1, self::ORG_ID + 1);
        $this->tree->addUserOrganization(self::USER_ID + 1, self::ORG_ID + 2);
        $this->tree->addUserOrganization(self::USER_ID + 2, self::ORG_ID + 2);
        $this->tree->addUserOrganization(self::USER_ID + 3, self::ORG_ID + 2);
        $this->tree->addUserOrganization(self::USER_ID + 3, self::ORG_ID + 3);
        $this->tree->addUserOrganization(self::USER_ID + 31, self::ORG_ID + 3);
        $this->tree->addUserOrganization(self::USER_ID + 4, self::ORG_ID + 4);
        $this->tree->addUserOrganization(self::USER_ID + 411, self::ORG_ID + 4);

        $this->tree->addUserBusinessUnit(self::USER_ID + 1, self::ORG_ID + 1, self::BU_ID + 1);
        $this->tree->addUserBusinessUnit(self::USER_ID + 1, self::ORG_ID + 2, self::BU_ID + 2);
        $this->tree->addUserBusinessUnit(self::USER_ID + 2, self::ORG_ID + 2, self::BU_ID + 2);
        $this->tree->addUserBusinessUnit(self::USER_ID + 3, self::ORG_ID + 3, self::BU_ID + 3);
        $this->tree->addUserBusinessUnit(self::USER_ID + 3, self::ORG_ID + 2, self::BU_ID + 2);
        $this->tree->addUserBusinessUnit(self::USER_ID + 31, self::ORG_ID + 3, self::BU_ID + 31);
        $this->tree->addUserBusinessUnit(self::USER_ID + 4, self::ORG_ID + 4, self::BU_ID + 4);
        $this->tree->addUserBusinessUnit(self::USER_ID + 411, self::ORG_ID + 4, self::BU_ID + 411);
    }

    protected function buildTree()
    {
        $subordinateBusinessUnits = [
            self::BU_ID + 3  => [self::BU_ID + 31],
            self::BU_ID + 32 => [self::BU_ID + 321],
            self::BU_ID + 41 => [self::BU_ID + 411],
            self::BU_ID + 4  => [self::BU_ID + 41, self::BU_ID + 411],

        ];

        foreach ($subordinateBusinessUnits as $parentBuId => $buIds) {
            $this->tree->setSubordinateBusinessUnitIds($parentBuId, $buIds);
        }
    }
}
