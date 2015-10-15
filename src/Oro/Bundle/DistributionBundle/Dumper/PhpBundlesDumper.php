<?php

namespace Oro\Bundle\DistributionBundle\Dumper;

class PhpBundlesDumper
{
    /**
     * @var array
     */
    private $bundles;

    /**
     * Constructor.
     *
     * @param array $bundles Bundles collection to dump
     */
    public function __construct($bundles)
    {
        $this->bundles = $bundles;
    }

    /**
     * Dumps a set of bundles to a PHP array.
     */
    public function dump()
    {
        return <<<EOF
<?php
/**
 * This class alias has done to optimize JMSJobQueueBundle boot.
 */
if (!class_exists('JMS\JobQueueBundle\JMSJobQueueBundle', false)) {
    class_alias(
        'JMS\JobQueueBundle\JMSJobQueueBundle',
        'Oro\Bundle\CronBundle\JobQueueBundle\JMSJobQueueBundle'
    );
}

return {$this->generateBundlesArray()}

EOF;
    }

    /**
     * Generates PHP code representing an array of defined "autoregistered" bundle instances.
     *
     * @return string PHP code
     */
    private function generateBundlesArray()
    {
        $bundles = "array(\n";

        foreach ($this->bundles as $class => $params) {
            $bundles .= sprintf("    new %s(%s),\n", $class, $params['kernel'] ? '$this' : '');
        }

        $bundles .= ');';

        return $bundles;
    }
}
