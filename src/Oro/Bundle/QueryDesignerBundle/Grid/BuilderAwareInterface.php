<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid;

/**
 * This interface could be implemented by datagrid configuration providers
 * that are aware about the datagrid builder.
 */
interface BuilderAwareInterface
{
    public function getBuilder(): DatagridConfigurationBuilder;
}
