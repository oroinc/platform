<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

trait SkipIsolatorsTrait
{
    /** @var  bool */
    protected $skip;

    /** @var  null|array */
    protected $skipIsolators;

    /**
     * @param bool $skip
     */
    public function setSkip($skip)
    {
        $this->skip = $skip;
    }

    /**
     * @param null|array $skipIsolators
     */
    public function setSkipIsolators($skipIsolators)
    {
        $this->skipIsolators = $skipIsolators;
    }
}
