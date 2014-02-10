<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Entity()
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  routeName="orocrm_account_index",
 *  routeView="orocrm_account_view",
 *  defaultValues={
 *      "ownership"={
 *          "owner_type"="USER",
 *          "owner_field_name"="owner",
 *          "owner_column_name"="user_owner_id"
 *      },
 *      "security"={
 *          "type"="ACL",
 *          "group_name"=""
 *      }
 *  }
 * )
 */
class TestClass extends ExtendTestClass
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $description;
}
