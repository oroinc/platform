<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Stub;

class ImportEntity extends BaseImportEntity
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var
     */
    protected $twitter;

    /**
     * @var
     */
    private $private;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getTwitter()
    {
        return $this->twitter;
    }

    /**
     * @param mixed $twitter
     */
    public function setTwitter($twitter)
    {
        $this->twitter = $twitter;
    }
}
