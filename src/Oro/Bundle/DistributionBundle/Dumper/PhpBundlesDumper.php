<?php

namespace Oro\Bundle\DistributionBundle\Dumper;

class PhpBundlesDumper
{
    /**
     * @var array
     */
    private $bundles;

    private $bundleAliases;

    /**
     * Constructor.
     *
     * @param array $bundles Bundles collection to dump
     */
    public function __construct($bundles, $bundleAliases = array())
    {
        $this->bundles = $bundles;
        $this->bundleAliases = $bundleAliases;
    }

    /**
     * Dumps a set of bundles to a PHP array.
     */
    public function dump()
    {
        return <<<EOF
<?php
{$this->generateBundlesAliases()}
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

    private function generateBundlesAliases()
    {
        $output = '';
        if (!empty($this->bundleAliases)) {
            foreach ($this->bundleAliases as $parent => $override) {
                $output .= sprintf(
                    "if (!class_exists('%s', false)) {
                    class_alias(
                       '%s',
                       '%s'
                    );
                }\n",
                    $parent,
                    $parent,
                    $override
                );

            }
        }

        return $output;
    }
}
