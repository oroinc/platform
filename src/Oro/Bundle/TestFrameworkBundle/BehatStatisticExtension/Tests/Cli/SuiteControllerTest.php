<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Cli;

use Behat\Testwork\Suite\Generator\SuiteGenerator;
use Behat\Testwork\Suite\GenericSuite;
use Behat\Testwork\Suite\SuiteRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli\SuiteController;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatisticManager;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Stub\InputStub;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;

class SuiteControllerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureStatisticManager|\PHPUnit\Framework\MockObject\MockObject */
    private $featureStatisticManager;

    /** @var array */
    private $suites = [
        'AcmeDemo1',
        'AcmeDemo2',
        'AcmeDemo3',
    ];

    /** @var array */
    private $suiteSets = [
        'First' => ['AcmeDemo1', 'AcmeDemo2'],
        'Second' => ['AcmeDemo3'],
    ];

    protected function setUp(): void
    {
        $this->featureStatisticManager = $this->createMock(FeatureStatisticManager::class);
    }

    public function testConfigure()
    {
        $suiteRegistry = new SuiteRegistry();
        $controller = new SuiteController(
            $this->getSuiteConfigurationRegistryMock(),
            $suiteRegistry,
            $this->featureStatisticManager
        );
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
        $generator = $this->createMock(SuiteGenerator::class);
        $generator->method('supportsTypeAndSettings')->willReturn(true);
        $generator->method('generateSuite')->willReturnArgument(0);

        $suiteRegistry = new SuiteRegistry();
        $suiteRegistry->registerSuiteGenerator($generator);

        $controller = new SuiteController(
            $this->getSuiteConfigurationRegistryMock(),
            $suiteRegistry,
            $this->featureStatisticManager
        );
        $options = [
            'suite' => $suite,
            'suite-set' => $suiteSet,
        ];
        $controller->execute(new InputStub('', [], $options), new OutputStub());

        $this->assertSame($expectedRegisteredSuites, $suiteRegistry->getSuites());
    }

    /**
     * @return array
     */
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
     * @return SuiteConfigurationRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getSuiteConfigurationRegistryMock()
    {
        $suiteConfigRegistry = $this->createMock(SuiteConfigurationRegistry::class);

        $suiteConfigRegistry->method('getSuiteConfig')->willReturnCallback(function ($name) {
            return new GenericSuite($name, ['type' => null, 'paths' => []]);
        });

        $suiteConfigRegistry->method('getSet')->willReturnCallback(function ($set) {
            return array_map(function ($name) {
                return new GenericSuite($name, ['type' => null, 'paths' => []]);
            }, $this->suiteSets[$set]);
        });

        $suiteConfigRegistry->method('getSuites')->willReturn(array_map(function ($name) {
            return new GenericSuite($name, ['type' => null, 'paths' => []]);
        }, $this->suites));

        return $suiteConfigRegistry;
    }
}
