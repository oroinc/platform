<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid;

interface BuilderAwareInterface
{
    /**
     * @return DatagridConfigurationBuilder
     */
    public function getBuilder();
}
