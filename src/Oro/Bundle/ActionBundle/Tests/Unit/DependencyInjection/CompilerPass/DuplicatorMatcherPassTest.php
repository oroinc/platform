<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\DuplicatorMatcherPass;

class DuplicatorMatcherPassTest extends AbstractDuplicatorPassTest
{
    protected function setUp(): void
    {
        $this->compilerPass = new DuplicatorMatcherPass();
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return DuplicatorMatcherPass::FACTORY_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return DuplicatorMatcherPass::TAG_NAME;
    }
}
