<?php

namespace Oro\Component\ConfigExpression;

interface ExpressionAssemblerAwareInterface
{
    /**
     * Setter for expression assembler
     *
     * @param ExpressionAssembler $assembler
     *
     * @return void
     */
    public function setAssembler(ExpressionAssembler $assembler);
}
