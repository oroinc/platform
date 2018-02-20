<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="test_api_default_and_null")
 * @ORM\Entity
 */
class TestDefaultAndNull implements TestFrameworkEntityInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="with_default_value_string", type="string", nullable=true)
     */
    public $withDefaultValueString;

    /**
     * @var string
     *
     * @ORM\Column(name="without_default_value_string", type="string", nullable=true)
     */
    public $withoutDefaultValueString;

    /**
     * @var boolean
     *
     * @ORM\Column(name="with_default_value_boolean", type="boolean", nullable=true)
     */
    public $withDefaultValueBoolean;

    /**
     * @var boolean
     *
     * @ORM\Column(name="without_default_value_boolean", type="boolean", nullable=true)
     */
    public $withoutDefaultValueBoolean;

    /**
     * @var integer
     *
     * @ORM\Column(name="with_default_value_integer", type="integer", nullable=true)
     */
    public $withDefaultValueInteger;

    /**
     * @var integer
     *
     * @ORM\Column(name="without_default_value_integer", type="integer", nullable=true)
     */
    public $withoutDefaultValueInteger;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(name="with_df_not_blank", type="string", nullable=true)
     */
    public $withDefaultValueAndNotBlank;

    /**
     * @var string
     *
     * @Assert\NotNull
     * @ORM\Column(name="with_df_not_null", type="string", nullable=true)
     */
    public $withDefaultValueAndNotNull;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(name="with_not_blank", type="string", nullable=true)
     */
    public $withNotBlank;

    /**
     * @var string
     *
     * @Assert\NotNull
     * @ORM\Column(name="with_not_null", type="string", nullable=true)
     */
    public $withNotNull;

    public function __construct()
    {
        $this->withDefaultValueString = 'default';
        $this->withDefaultValueBoolean = false;
        $this->withDefaultValueInteger = 0;
        $this->withDefaultValueAndNotBlank = 'default_NotBlank';
        $this->withDefaultValueAndNotNull = 'default_NotNull';
    }
}
