<?php

namespace Oro\Bundle\AsseticBundle\Tests\Unit\Twig;

use \Twig_Token;
use \Twig_TokenStream;

use Oro\Bundle\AsseticBundle\Twig\AsseticTokenParser;

class AsseticTokenParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $assetsConfiguration;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $assetFactory;

    /**
     * @var AsseticTokenParser
     */
    private $parser;

    /**
     * @var string
     */
    private $tagName = 'oro_css';

    /**
     * @var string
     */
    private $output = 'css/*.css';

    public function setUp()
    {
        $this->assetsConfiguration = $this->getMockBuilder('Oro\Bundle\AsseticBundle\AssetsConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assetFactory = $this->getMockBuilder('Symfony\Bundle\AsseticBundle\Factory\AssetFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->tagName = 'oro_css';

        $this->parser = new AsseticTokenParser(
            $this->assetsConfiguration,
            $this->assetFactory,
            $this->tagName,
            $this->output
        );
    }

    public function testGetTag()
    {
        $this->assertEquals($this->tagName, $this->parser->getTag());
    }

    public function testTestEndTag()
    {
        $token = new Twig_Token(Twig_Token::NAME_TYPE, 'end' . $this->tagName, 31);
        $this->assertTrue($this->parser->testEndTag($token));
    }

    public function testParse()
    {
        $parser = $this->getMockBuilder('Twig_Parser')
            ->disableOriginalConstructor()
            ->getMock();

        $startToken = new Twig_Token(Twig_Token::NAME_TYPE, 'oro_css', 31);

        $stream = new Twig_TokenStream(
            array(
                new Twig_Token(Twig_Token::NAME_TYPE, 'filter', 31),
                new Twig_Token(Twig_Token::OPERATOR_TYPE, '=', 31),
                new Twig_Token(Twig_Token::STRING_TYPE, 'cssrewrite, lessphp, ?cssmin', 31),
                new Twig_Token(Twig_Token::NAME_TYPE, 'debug', 31),
                new Twig_Token(Twig_Token::OPERATOR_TYPE, '=', 31),
                new Twig_Token(Twig_Token::NAME_TYPE, 'false', 31),
                new Twig_Token(Twig_Token::NAME_TYPE, 'combine', 31),
                new Twig_Token(Twig_Token::OPERATOR_TYPE, '=', 31),
                new Twig_Token(Twig_Token::NAME_TYPE, 'false', 31),
                new Twig_Token(Twig_Token::NAME_TYPE, 'output', 31),
                new Twig_Token(Twig_Token::OPERATOR_TYPE, '=', 31),
                new Twig_Token(Twig_Token::STRING_TYPE, 'css/oro_app.css', 31),
                new Twig_Token(Twig_Token::BLOCK_END_TYPE, '', 31),
                new Twig_Token(Twig_Token::BLOCK_END_TYPE, '', 32),
                new Twig_Token(Twig_Token::BLOCK_START_TYPE, '', 33),
                new Twig_Token(Twig_Token::NAME_TYPE, 'endoro_css', 33),
                new Twig_Token(Twig_Token::BLOCK_END_TYPE, '', 33),
                new Twig_Token(Twig_Token::EOF_TYPE, '', 31),
            )
        );

        $bodyNode = $this->getMockBuilder('\Twig_Node')
            ->disableOriginalConstructor()
            ->getMock();

        $parser->expects($this->once())
            ->method('subparse')
            ->will($this->returnValue($bodyNode));

        $parser->expects($this->once())
            ->method('getStream')
            ->will($this->returnValue($stream));

        $this->parser->setParser($parser);

        $assert = $this->getMockBuilder('Assetic\Asset\AssetCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assetFactory->expects($this->atLeastOnce())->method('createAsset')->will($this->returnValue($assert));

        $this->assetsConfiguration->expects($this->at(0))
            ->method('getCssFiles')
            ->with(false)
            ->will(
                $this->returnValue(
                    array('foo.css')
                )
            );

        $this->assetsConfiguration->expects($this->at(1))
            ->method('getCssFiles')
            ->with(true)
            ->will(
                $this->returnValue(
                    array('bar.css')
                )
            );

        /**
         * @var \Symfony\Bundle\AsseticBundle\Twig\AsseticNode
         */
        $resultNode = $this->parser->parse($startToken);

        $this->assertEquals(31, $resultNode->getLine());
        $nodes = $resultNode->getIterator()->getArrayCopy();
        $this->assertCount(2, $nodes);

        $this->assertInstanceOf('Symfony\Bundle\AsseticBundle\Twig\AsseticNode', $nodes[0]);
        $this->assertEquals('oro_css', $nodes[0]->getNodeTag());

        $this->assertInstanceOf('Oro\Bundle\AsseticBundle\Twig\DebugAsseticNode', $nodes[1]);
        $this->assertEquals('oro_css', $nodes[1]->getNodeTag());
    }

    public function testParseBrokenStream()
    {
        $parser = $this->getMockBuilder('Twig_Parser')
            ->disableOriginalConstructor()
            ->getMock();

        $brokenStream = new Twig_TokenStream(
            array(
                new Twig_Token(Twig_Token::NAME_TYPE, 'bad', 31),
                new Twig_Token(Twig_Token::OPERATOR_TYPE, '=', 31),
                new Twig_Token(Twig_Token::STRING_TYPE, 'bad value', 31),
            )
        );

        $parser->expects($this->once())
            ->method('getStream')
            ->will($this->returnValue($brokenStream));

        $this->parser->setParser($parser);

        $this->setExpectedException('Twig_Error_Syntax');

        $this->parser->parse(new Twig_Token(Twig_Token::NAME_TYPE, 'oro_css', 31));
    }
}
