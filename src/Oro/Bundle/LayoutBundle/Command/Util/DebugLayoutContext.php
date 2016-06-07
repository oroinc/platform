<?php

namespace Oro\Bundle\LayoutBundle\Command\Util;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\LayoutContext;

class DebugLayoutContext extends LayoutContext
{
    /**
     * @var DebugOptionsResolverDecorator
     */
    protected $decorator;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->decorator = new DebugOptionsResolverDecorator(new OptionsResolver());
        parent::__construct();
    }

    /**
     * @return DebugOptionsResolverDecorator
     */
    public function getOptionsResolverDecorator()
    {
        return $this->decorator;
    }

    /**
     * {@inheritdoc}
     */
    protected function createResolver()
    {
        return $this->decorator->getOptionResolver();
    }
}
