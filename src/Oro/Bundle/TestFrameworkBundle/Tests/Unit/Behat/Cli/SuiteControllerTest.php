<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli;

use Behat\Testwork\Suite\Generator\SuiteGenerator;
use Behat\Testwork\Suite\SuiteRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Cli\SuiteController;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SuiteConfiguration;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SuiteConfigurationRegistry;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;

class SuiteControllerTest extends \PHPUnit_Framework_TestCase
{
    private $suites = [
        'AcmeDemo1',
        'AcmeDemo2',
        'AcmeDemo3',
    ];

    private $suiteSets = [
        'First' => ['AcmeDemo1', 'AcmeDemo2'],
        'Second' => ['AcmeDemo3'],
    ];


    public function testConfigure()
    {
        $suiteRegistry = new SuiteRegistry();
        $controller = new SuiteController($this->getSuiteConfigurationRegistryMock(), $suiteRegistry);
        $command = new Command('test');

        $controller->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('suite'));
        $this->assertTrue($command->getDefinition()->getOption('suite')->isValueRequired());
        $this->assertTrue($command->getDefinition()->getOption('suite-set')->isValueRequired());
        $this->assertTrue($command->getDefinition()->getOption('suite-set')->isValueRequired());
    }

    /**
     * @dataProvider executeData
     * @param string $suite
     * @param string $suiteSet
     * @param array $expectedRegisteredSuites
     */
    public function testExecute($suite, $suiteSet, array $expectedRegisteredSuites)
    {
        $generator = $this->getMockBuilder(SuiteGenerator::class)->getMock();
        $generator->method('supportsTypeAndSettings')->willReturn(true);
        $generator->method('generateSuite')->willReturnArgument(0);

        $suiteRegistry = new SuiteRegistry();
        $suiteRegistry->registerSuiteGenerator($generator);

        $controller = new SuiteController($this->getSuiteConfigurationRegistryMock(), $suiteRegistry);
        $options = [
            'suite' => $suite,
            'suite-set' => $suiteSet,
        ];
        $controller->execute(new InputStub('', [], $options), new OutputStub());

        $this->assertSame($expectedRegisteredSuites, $suiteRegistry->getSuites());
    }

    public function executeData()
    {
        return [
            'Register Suite' => [
                'suite' => 'AcmeDemo1',
                'set' => null,
                'expectedSuites' => ['AcmeDemo1'],
            ],
            'Register First Set' => [
                'suite' => null,
                'set' => 'First',
                'expectedSuites' => $this->suiteSets['First'],
            ],
            'Register Second Set' => [
                'suite' => null,
                'set' => 'Second',
                'expectedSuites' => $this->suiteSets['Second'],
            ],
            'Register All Suites' => [
                'suite' => null,
                'set' => null,
                'expectedSuites' => $this->suites,
            ],
        ];
    }

    /**
     * @return SuiteConfigurationRegistry
     */
    private function getSuiteConfigurationRegistryMock()
    {
        $suiteConfigRegistry = $this
            ->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $suiteConfigRegistry->method('getSuiteConfig')->willReturnCallback(function ($suiteName) {
            return new SuiteConfiguration($suiteName);
        });

        $suiteConfigRegistry->method('getSet')->willReturnCallback(function ($set) {
            return array_map(function ($suite) {
                return new SuiteConfiguration($suite);
            }, $this->suiteSets[$set]);
        });

        $suiteConfigRegistry->method('getSuiteConfigurations')->willReturn(array_map(function ($suite) {
            return new SuiteConfiguration($suite);
        }, $this->suites));

        return $suiteConfigRegistry;
    }
}
