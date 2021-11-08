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
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new ScopeCollectionType();

        parent::setUp();
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

        $this->assertArrayHasKey('entry_type', $options);
        $this->assertEquals(ScopeType::class, $options['entry_type']);

        $this->assertArrayHasKey('handle_primary', $options);
        $this->assertFalse($options['handle_primary']);
    }
}
