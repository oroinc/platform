<?php

namespace Oro\Bundle\AsseticBundle\Twig;

use Assetic\Asset\AssetInterface;

class DebugAsseticNode extends \Twig_Node
{
    /**
     * @param AssetInterface $asset
     * @param \Twig_NodeInterface $body
     * @param array $inputs
     * @param array $filters
     * @param string $name
     * @param array $attributes
     * @param int $lineno
     * @param string $tag
     */
    public function __construct(
        AssetInterface $asset,
        \Twig_NodeInterface $body,
        array $inputs,
        array $filters,
        $name,
        array $attributes = array(),
        $lineno = 0,
        $tag = null
    ) {
        $nodes = array('body' => $body);

        $attributes = array_replace(
            array('debug' => null, 'combine' => null, 'var_name' => 'asset_url'),
            $attributes,
            array('asset' => $asset, 'inputs' => $inputs, 'filters' => $filters, 'name' => $name)
        );

        parent::__construct($nodes, $attributes, $lineno, $tag);
    }

    /**
     * {@inheritDoc}
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $i = 0;
        foreach ($this->getAttribute('asset') as $leaf) {
            $leafName = $this->getAttribute('name') . '_' . $i++;
            $this->compileAsset($compiler, $leaf, $leafName);
        }

        $compiler
            ->write('unset($context[')
            ->repr($this->getAttribute('var_name'))
            ->raw("]);\n");
    }

    /**
     * @param \Twig_Compiler $compiler
     * @param AssetInterface $asset
     * @param string $name
     */
    protected function compileAsset(\Twig_Compiler $compiler, AssetInterface $asset, $name)
    {
        $compiler
            ->write("// asset \"$name\"\n")
            ->write('$context[')
            ->repr($this->getAttribute('var_name'))
            ->raw('] = ');

        $this->compileAssetUrl($compiler, $asset, $name);

        $compiler
            ->raw(";\n")
            ->subcompile($this->getNode('body'));
    }

    /**
     * @param \Twig_Compiler $compiler
     * @param AssetInterface $asset
     */
    protected function compileAssetUrl(\Twig_Compiler $compiler, AssetInterface $asset)
    {
        $compiler
            ->raw('$this->env->getExtension(\'assets\')->getAssetUrl(')
            ->repr($asset->getSourcePath())
            ->raw(')');

        return;
    }
}
