<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScopeCollectionTypeTest extends FormIntegrationTestCase
{
    /** @var ScopeCollectionType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new ScopeCollectionType();

        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->formType);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_scope_collection', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('type', $options);
        $this->assertEquals(ScopeType::NAME, $options['type']);

        $this->assertArrayHasKey('handle_primary', $options);
        $this->assertFalse($options['handle_primary']);
    }
}
