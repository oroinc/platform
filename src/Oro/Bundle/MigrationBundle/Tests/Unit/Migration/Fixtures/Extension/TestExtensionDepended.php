<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension;

class TestExtensionDepended implements TestExtensionAwareInterface
{
    protected $testExtension;

    public function setTestExtension(TestExtension $testExtension)
    {
        $this->testExtension = $testExtension;
    }

    public function getTestExtension()
    {
        return $this->testExtension;
    }
}
