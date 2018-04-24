<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer\Stub;

class EntityStub
{
    /**
     * @var mixed
     */
    private $readable;

    /**
     * @var string
     */
    private $notReadable = 'some value';

    /**
     * @var bool
     */
    public $reloaded = false;

    /**
     * @return mixed
     */
    public function getReadable()
    {
        return $this->readable;
    }

    /**
     * @param mixed $readable
     */
    public function setReadable($readable)
    {
        $this->readable = $readable;
    }
}
