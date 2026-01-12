<?php

namespace Oro\Bundle\DistributionBundle\Dumper;

/**
 * Generates PHP code for bundle registration in the application kernel.
 *
 * This dumper converts bundle configuration metadata into executable PHP code that instantiates
 * bundle objects. The generated code is cached and used by OroKernel during bundle registration,
 * enabling automatic bundle discovery and registration based on bundles.yml configuration files.
 */
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
