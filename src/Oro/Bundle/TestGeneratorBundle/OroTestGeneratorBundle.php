<?php

namespace Oro\Bundle\TestGeneratorBundle;

use Oro\Bundle\TestGeneratorBundle\DependencyInjection\OroTestGeneratorExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroTestGeneratorBundle extends Bundle
{
    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroTestGeneratorExtension();
    }
}
