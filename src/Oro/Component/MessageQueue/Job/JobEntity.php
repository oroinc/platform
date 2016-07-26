<?php
namespace Oro\Component\MessageQueue\Job;

class JobEntity extends Job
{
    /**
     * @internal
     *
     * @var string
     */
    protected $uniqueName;

    /**
     * @internal
     *
     * @return string
     */
    public function getUniqueName()
    {
        return $this->uniqueName;
    }

    /**
     * @internal
     *
     * @param string $uniqueName
     */
    public function setUniqueName($uniqueName)
    {
        $this->uniqueName = $uniqueName;
    }
}
