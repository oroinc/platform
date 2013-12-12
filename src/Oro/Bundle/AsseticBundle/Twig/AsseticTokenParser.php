<?php

namespace Oro\Bundle\AsseticBundle\Twig;

use Assetic\Factory\AssetFactory;

use Symfony\Bundle\AsseticBundle\Twig\AsseticNode;

use Oro\Bundle\AsseticBundle\AssetsConfiguration;

class AsseticTokenParser extends \Twig_TokenParser
{
    /**
     * @var AssetsConfiguration
     */
    private $assetsConfiguration;

    /**
     * @var AssetFactory
     */
    private $assetFactory;

    /**
     * @var string
     */
    private $tag;

    /**
     * @var string
     */
    private $output;

    /**
     * @param AssetsConfiguration $assetsConfiguration
     * @param AssetFactory $assetFactory
     * @param string $tag
     * @param string $output
     */
    public function __construct(AssetsConfiguration $assetsConfiguration, AssetFactory $assetFactory, $tag, $output)
    {
        $this->assetsConfiguration = $assetsConfiguration;
        $this->assetFactory = $assetFactory;
        $this->tag = $tag;
        $this->output = $output;
    }

    /**
     * {@inheritDoc}
     */
    public function parse(\Twig_Token $token)
    {
        $filters = array();

        $attributes = array(
            'output' => $this->output,
            'var_name' => 'asset_url',
            'vars' => array(),
        );

        $stream = $this->parser->getStream();

        while (!$stream->test(\Twig_Token::BLOCK_END_TYPE)) {
            if ($stream->test(\Twig_Token::NAME_TYPE, 'filter')) {
                $filters = array_merge(
                    $filters,
                    array_filter(array_map('trim', explode(',', $this->parseStringValue($stream))))
                );
            } elseif ($stream->test(\Twig_Token::NAME_TYPE, 'output')) {
                $attributes['output'] = $this->parseStringValue($stream);
            } elseif ($stream->test(\Twig_Token::NAME_TYPE, 'debug')) {
                $attributes['debug'] = $this->parseBooleanValue($stream);
            } elseif ($stream->test(\Twig_Token::NAME_TYPE, 'combine')) {
                $attributes['combine'] = $this->parseBooleanValue($stream);
            } else {
                $token = $stream->getCurrent();
                throw new \Twig_Error_Syntax(
                    sprintf(
                        'Unexpected token "%s" of value "%s"',
                        \Twig_Token::typeToEnglish($token->getType(), $token->getLine()),
                        $token->getValue()
                    ),
                    $token->getLine()
                );
            }
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(array($this, 'testEndTag'), true);
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        $lineno = $token->getLine();

        return new \Twig_Node(
            array(
                $this->createAsseticNode(
                    $body,
                    $filters,
                    $attributes,
                    $lineno
                ),
                $this->createDebugAsseticNode(
                    $body,
                    $filters,
                    $attributes,
                    $lineno
                )
            ),
            array(),
            $lineno
        );
    }

    /**
     * @param \Twig_NodeInterface $body
     * @param array $filters
     * @param array $attributes
     * @param int $lineno
     * @return AsseticNode
     */
    protected function createAsseticNode(
        \Twig_NodeInterface $body,
        array $filters,
        array $attributes,
        $lineno
    ) {
        $inputs = $this->assetsConfiguration->getCssFiles(false);

        $name = $this->assetFactory->generateAssetName($inputs, $filters, $attributes);
        $asset = $this->assetFactory->createAsset($inputs, $filters, $attributes + array('name' => $name));

        return new AsseticNode($asset, $body, $inputs, $filters, $name, $attributes, $lineno, $this->getTag());
    }

    /**
     * @param \Twig_NodeInterface $body
     * @param array $filters
     * @param array $attributes
     * @param int $lineno
     * @return AsseticNode
     */
    protected function createDebugAsseticNode(
        \Twig_NodeInterface $body,
        array $filters,
        array $attributes,
        $lineno
    ) {
        $inputs = $this->assetsConfiguration->getCssFiles(true);

        $name = $this->assetFactory->generateAssetName($inputs, $filters, $attributes);
        $asset = $this->assetFactory->createAsset($inputs, $filters, $attributes + array('name' => $name));

        return new DebugAsseticNode($asset, $body, $inputs, $filters, $name, $attributes, $lineno, $this->getTag());
    }

    /**
     * {@inheritDoc}
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Test for end tag
     *
     * @param \Twig_Token $token
     * @return bool
     */
    public function testEndTag(\Twig_Token $token)
    {
        return $token->test(array('end' . $this->tag));
    }

    /**
     * Get boolean value from stream
     *
     * @param \Twig_TokenStream $stream
     * @return bool
     */
    protected function parseBooleanValue(\Twig_TokenStream $stream)
    {
        $stream->next();
        $stream->expect(\Twig_Token::OPERATOR_TYPE, '=');

        return 'true' == $stream->expect(\Twig_Token::NAME_TYPE, array('true', 'false'))->getValue();
    }

    /**
     * Get string value from stream
     *
     * @param \Twig_TokenStream $stream
     * @return string
     */
    protected function parseStringValue(\Twig_TokenStream $stream)
    {
        $stream->next();
        $stream->expect(\Twig_Token::OPERATOR_TYPE, '=');

        return $stream->expect(\Twig_Token::STRING_TYPE)->getValue();
    }
}
