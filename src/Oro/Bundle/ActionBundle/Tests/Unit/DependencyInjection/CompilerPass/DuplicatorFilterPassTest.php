<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\DuplicatorFilterPass;

class DuplicatorFilterPassTest extends AbstractDuplicatorPassTest
{
    protected function setUp(): void
    {
        $this->compilerPass = new DuplicatorFilterPass();
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return DuplicatorFilterPass::FACTORY_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return DuplicatorFilterPass::TAG_NAME;
    }
}
