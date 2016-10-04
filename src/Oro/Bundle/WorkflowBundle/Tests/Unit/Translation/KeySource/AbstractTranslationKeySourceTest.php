<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeySource;

use Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeySource\Stub\TranslationKeySourceStub;

class AbstractTranslationKeySourceTest extends TranslationKeySourceTestCase
{
    public function testConstructAndGetData()
    {
        $workflowName = 'test_workflow_name';
        $workflow = $this->getWorkflow($workflowName);
        $data = ['test_key' => 'test_value'];

        $keySource = new TranslationKeySourceStub($workflow, $data);

        $this->assertEquals(
            ['test_key' => 'test_value', 'workflow_name' => 'test_workflow_name'],
            $keySource->getData()
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected not empty value for key "workflow_name" in data, null given
     */
    public function testConstructException()
    {
        $workflow = $this->getWorkflow();
        $data = ['test_key' => 'test_value'];

        TranslationKeySourceStub::$requiredKeys = ['workflow_name'];

        new TranslationKeySourceStub($workflow, $data);
    }
}
