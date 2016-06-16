<?php
/**
 * Created by PhpStorm.
 * User: Matey
 * Date: 16.06.2016
 * Time: 17:49
 */

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub;

class EntitySub
{
    /** @var integer */
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}