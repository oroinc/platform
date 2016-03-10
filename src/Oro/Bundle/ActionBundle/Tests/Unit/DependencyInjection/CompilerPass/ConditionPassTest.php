<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConditionPass;

class ConditionPassTest extends AbstractPassTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->compilerPass = new ConditionPass();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return ConditionPass::EXTENSION_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return ConditionPass::EXPRESSION_TAG;
    }
}
