<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionCollection;

class FileResource
{
    /** @var string */
    protected $filename;

    /** @var ConditionCollection */
    protected $conditionCollection;

    /**
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->filename            = $filename;
        $this->conditionCollection = new ConditionCollection();
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return ConditionCollection
     */
    public function getConditions()
    {
        return $this->conditionCollection;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFilename();
    }
}
