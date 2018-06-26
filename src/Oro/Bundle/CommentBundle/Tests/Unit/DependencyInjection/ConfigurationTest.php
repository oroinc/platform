<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CommentBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);

        $root = $builder->buildTree();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\ArrayNode', $root);
        $this->assertEquals('oro_comment', $root->getName());
    }
}
