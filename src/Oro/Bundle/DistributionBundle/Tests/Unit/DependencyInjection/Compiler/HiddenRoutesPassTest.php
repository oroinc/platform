<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\DistributionBundle\DependencyInjection\Compiler\HiddenRoutesPass;

class HiddenRoutesPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var HiddenRoutesPass */
    protected $compilerPass;

    protected function setUp()
    {
        $this->compilerPass = new HiddenRoutesPass();
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess($params, $expectedParams)
    {
        $container = new ContainerBuilder();
        foreach ($params as $name => $val) {
            $container->getParameterBag()->set($name, $val);
        }

        $this->compilerPass->process($container);

        $this->assertEquals(
            $expectedParams,
            $container->getParameterBag()->all()
        );
    }

    public function processDataProvider()
    {
        return [
            [[], []],
            [
                [
                    HiddenRoutesPass::MATCHER_DUMPER_CLASS_PARAM =>
                        HiddenRoutesPass::EXPECTED_MATCHER_DUMPER_CLASS
                ],
                [
                    HiddenRoutesPass::MATCHER_DUMPER_CLASS_PARAM =>
                        HiddenRoutesPass::NEW_MATCHER_DUMPER_CLASS
                ]
            ],
            [
                [
                    HiddenRoutesPass::MATCHER_DUMPER_CLASS_PARAM => 'OtherMatcherDumper'
                ],
                [
                    HiddenRoutesPass::MATCHER_DUMPER_CLASS_PARAM => 'OtherMatcherDumper'
                ]
            ],
            [
                [
                    HiddenRoutesPass::API_DOC_EXTRACTOR_CLASS_PARAM =>
                        HiddenRoutesPass::EXPECTED_API_DOC_EXTRACTOR_CLASS
                ],
                [
                    HiddenRoutesPass::API_DOC_EXTRACTOR_CLASS_PARAM =>
                        HiddenRoutesPass::NEW_API_DOC_EXTRACTOR_CLASS
                ]
            ],
            [
                [
                    HiddenRoutesPass::API_DOC_EXTRACTOR_CLASS_PARAM =>
                        HiddenRoutesPass::EXPECTED_CACHING_API_DOC_EXTRACTOR_CLASS
                ],
                [
                    HiddenRoutesPass::API_DOC_EXTRACTOR_CLASS_PARAM =>
                        HiddenRoutesPass::NEW_CACHING_API_DOC_EXTRACTOR_CLASS
                ]
            ],
            [
                [
                    HiddenRoutesPass::API_DOC_EXTRACTOR_CLASS_PARAM => 'OtherApiDocExtractor'
                ],
                [
                    HiddenRoutesPass::API_DOC_EXTRACTOR_CLASS_PARAM => 'OtherApiDocExtractor'
                ]
            ],
        ];
    }
}
