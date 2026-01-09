<?php

declare(strict_types=1);

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Twig\TitleNode;
use Oro\Bundle\NavigationBundle\Twig\TitleSetTokenParser;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Node;

class TitleSetTokenParserTest extends TestCase
{
    public function testParsing(): void
    {
        $loader = new ArrayLoader([
            'test' => '{% oro_title_set({"key": "value"}) %}'
        ]);

        $twig = new Environment($loader);
        $twig->addTokenParser(new TitleSetTokenParser());

        $result = $twig->parse($twig->tokenize($twig->getLoader()->getSourceContext('test')));

        $this->assertTrue($this->hasNeededTitleNode($result));
    }

    public function testTagName(): void
    {
        $tokenParser = new TitleSetTokenParser();
        $this->assertEquals('oro_title_set', $tokenParser->getTag());
    }

    private function hasNeededTitleNode(Node $node): bool
    {
        $bodyNode = $node->getNode('body');
        foreach ($bodyNode as $child) {
            if ($child instanceof TitleNode && $child->getNodeTag() === 'oro_title_set') {
                return true;
            }
        }

        return false;
    }
}
