<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Model\ExtendActivity;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="test_api_override_activity")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "grouping"={"groups"={"activity"}}
 *     }
 * )
 */
class TestOverrideClassActivity implements
    TestFrameworkEntityInterface,
    ActivityInterface,
    ExtendEntityInterface
{
    use ExtendActivity;
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    public $name;
}
