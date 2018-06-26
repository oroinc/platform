<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\ExtendTestActivity;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="test_api_activity")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "grouping"={"groups"={"activity"}}
 *     }
 * )
 */
class TestActivity extends ExtendTestActivity implements TestFrameworkEntityInterface
{
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
