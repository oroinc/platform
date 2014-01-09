<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TranslationBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProviderConfigTree
     */
    public function testConfigTree($options, $expects)
    {
        $processor     = new Processor();
        $configuration = new Configuration(array());
        $result        = $processor->processConfiguration($configuration, array($options));

        $this->assertEquals($expects, $result);
    }

    public function dataProviderConfigTree()
    {
        $settings = array(
            'available_translations' => array(
                'value' => [],
                'scope' => 'app'
            ),
            'resolved'               => true
        );

        return array(
            array(
                array(),
                array(
                    'js_translation'      => array(
                        'domains' => array('jsmessages', 'validators'),
                        'debug'   => '%kernel.debug%',
                    ),
                    'api'                 => array(
                        'crowdin'     => array(
                            'endpoint' => 'http://api.crowdin.net/api',
                        ),
                        'oro_service' => array(
                            'endpoint' => 'http://proxy.dev/api',
                            'key'      => ''
                        )
                    ),
                    'default_api_adapter' => 'crowdin',
                    'settings'            => $settings
                )
            ),
            array(
                array('js_translation' => array()),
                array(
                    'js_translation'      => array(
                        'domains' => array('jsmessages', 'validators'),
                        'debug'   => '%kernel.debug%',
                    ),
                    'api'                 => array(
                        'crowdin'     => array(
                            'endpoint' => 'http://api.crowdin.net/api',
                        ),
                        'oro_service' => array(
                            'endpoint' => 'http://proxy.dev/api',
                            'key'      => ''
                        )
                    ),
                    'default_api_adapter' => 'crowdin',
                    'settings'            => $settings
                )
            ),
            array(
                array(
                    'js_translation'      => array(
                        'domains' => array('validators'),
                        'debug'   => true,
                    ),
                    'api'                 => array(
                        'crowdin'     => array(
                            'endpoint' => 'http://google',
                        ),
                        'oro_service' => array(
                            'endpoint' => 'http://test.dev/api',
                            'key'      => 'testKey'
                        )
                    ),
                    'default_api_adapter' => 'crowdin',
                ),
                array(
                    'js_translation'      => array(
                        'domains' => array('validators'),
                        'debug'   => true,
                    ),
                    'api'                 => array(
                        'crowdin'     => array(
                            'endpoint' => 'http://google',
                        ),
                        'oro_service' => array(
                            'endpoint' => 'http://test.dev/api',
                            'key'      => 'testKey'
                        )
                    ),
                    'default_api_adapter' => 'crowdin',
                    'settings'            => $settings
                )
            ),
        );
    }
}
