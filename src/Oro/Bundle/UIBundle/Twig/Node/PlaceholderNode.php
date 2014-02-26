<?php

namespace Oro\Bundle\UIBundle\Twig\Node;

class PlaceholderNode extends \Twig_Node
{
    /**
     * @var \Twig_NodeInterface
     */
    protected $nameNode;

    /**
     * @var \Twig_NodeInterface
     */
    protected $variablesNode;

    /**
     * @param \Twig_NodeInterface $nameName
     * @param \Twig_NodeInterface $variablesNode
     * @param int $lineno
     * @param string $tag
     */
    public function __construct(\Twig_NodeInterface $nameName, \Twig_NodeInterface $variablesNode, $lineno, $tag)
    {
        parent::__construct(array(), array(), $lineno, $tag);
        $this->nameNode = $nameName;
        $this->variablesNode = $variablesNode;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $placeholderExpression = new \Twig_Node_Expression_Function(
            'placeholder',
            new \Twig_Node(
                array(
                    'name' => $this->nameNode,
                    'variables' => $this->variablesNode
                )
            ),
            $this->lineno
        );
        $block = new \Twig_Node_Print($placeholderExpression, $this->lineno);
        $block->compile($compiler);
    }
}
