<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TranslationBundle\DependencyInjection\OroTranslationExtension;
use Symfony\Component\DependencyInjection\Definition;

class OroTranslationExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    protected $expectedDefinitions = array(
        'oro_translation.form.type.translatable_entity',
        'oro_translation.form.type.select2_translatable_entity',
        'oro_translation.controller',
    );

    /**
     * @var array
     */
    protected $expectedParameters = array(
        'oro_translation.form.type.translatable_entity.class',
        'translator.class',
        'oro_translation.controller.class',
        'oro_translation.js_translation.domains',
        'oro_translation.debug_translator'
    );

    /**
     * @var array
     */
    protected $config = array(
        'oro_translation' => array(
            'js_translation' => array(
                'domains' => array('validators'),
                'debug' => false,
            ),
            'locales' => ['en'],
            'default_required' => true,
            'manager_registry' => 'doctrine',
            'templating' => 'foo.html.twig'
        )
    );

    public function testLoad()
    {
        $actualDefinitions = array();
        $actualParameters  = array();

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(array('setDefinition', 'setParameter', 'getDefinition'))
            ->getMock();
        $container->expects($this->any())
            ->method('setDefinition')
            ->will(
                $this->returnCallback(
                    function ($id, Definition $definition) use (&$actualDefinitions) {
                        $actualDefinitions[$id] = $definition;
                    }
                )
            );
        $container->expects($this->any())
            ->method('setParameter')
            ->will(
                $this->returnCallback(
                    function ($name, $value) use (&$actualParameters) {
                        $actualParameters[$name] = $value;
                    }
                )
            );
        $container->expects($this->any())
            ->method('getDefinition')
            ->will(
                $this->returnCallback(
                    function ($name) use (&$actualDefinitions) {
                        return $actualDefinitions[$name];
                    }
                )
            );

        $extension = new OroTranslationExtension();
        $extension->load($this->config, $container);

        foreach ($this->expectedDefinitions as $serviceId) {
            $this->assertArrayHasKey($serviceId, $actualDefinitions);
            $this->assertNotNull($actualDefinitions[$serviceId]);
        }

        foreach ($this->expectedParameters as $parameterName) {
            $this->assertArrayHasKey($parameterName, $actualParameters);
            $this->assertNotNull($actualParameters[$parameterName]);
        }
    }
}
