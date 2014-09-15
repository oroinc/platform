<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="test_table")
 * @ORM\Entity()
 */
class TestEntityWithEnum
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var TestEnumValue
     *
     * @ORM\ManyToOne(targetEntity="TestEnumValue")
     * @ORM\JoinColumn(name="singleEnumField_id", referencedColumnName="code")
     */
    protected $singleEnumField;

    /**
     * @var TestEnumValue
     *
     * @ORM\ManyToMany(targetEntity="TestEnumValue")
     * @ORM\JoinTable(name="oro_ref_enum_test",
     *      joinColumns={
     *          @ORM\JoinColumn(name="multipleEnumField_id", referencedColumnName="code")
     *      }
     * )
     */
    protected $multipleEnumField;
}
