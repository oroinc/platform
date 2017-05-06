<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli;

use Behat\Testwork\Specification\SpecificationFinder;
use Behat\Testwork\Suite\Generator\GenericSuiteGenerator;
use Behat\Testwork\Suite\SuiteRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Cli\AvailableSuitesGroupController;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli\Stub\SpecificationLocatorStub;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;

class AvailableSuitesGroupControllerTest extends \PHPUnit_Framework_TestCase
{
    private $suites = [
        'One',
        'Two',
        'Three',
        'Four',
        'Five',
        'Six',
    ];

    private $suiteGroups = [
        'First' => ['Two', 'Four'],
        'Second' => ['One'],
    ];

    public function testSkipExecutionWithoutOptions()
    {
        $suiteRegistry = new SuiteRegistry();
        $specificationFinder = new SpecificationFinder();
        $controller = new AvailableSuitesGroupController($suiteRegistry, $specificationFinder, []);
        $returnCode = $controller->execute(new InputStub(), new OutputStub());

        self::assertNull($returnCode);
    }

    /**
     * @dataProvider suitesGroupProvider
     */
    public function testSuitesGroup($groupName, $expectedSuites)
    {
        $suiteRegistry = new SuiteRegistry();
        $suiteRegistry->registerSuiteGenerator(new GenericSuiteGenerator());
        $suiteConfigurations = $this->createFakeConfigurations($this->suites);
        array_walk($suiteConfigurations, function (array $suiteConfig, $suiteName) use ($suiteRegistry) {
            $suiteRegistry->registerSuiteConfiguration($suiteName, $suiteConfig['type'], $suiteConfig['settings']);
        });
        $specificationFinder = new SpecificationFinder();
        $specificationLocator = new SpecificationLocatorStub($this->suites);
        $specificationFinder->registerSpecificationLocator($specificationLocator);
        $controller = new AvailableSuitesGroupController($suiteRegistry, $specificationFinder, $this->suiteGroups);

        $output = new OutputStub();

        $input = new InputStub('', [], ['available-suites-group' => $groupName]);
        $returnCode = $controller->execute($input, $output);
        self::assertSame(0, $returnCode);
        self::assertEquals($expectedSuites, $output->messages);
    }

    /**
     * @return array
     */
    public function suitesGroupProvider()
    {
        return [
            ['First', ['Two', 'Four']],
            ['Second', ['One']],
            ['Ungrouped', ['Three', 'Five', 'Six']],
        ];
    }

    /**
     * @param array $availableSuites in format ['SuiteNameOne', 'SuiteNameTwo']
     * @return array in format ['SuiteNameOne' => ['type' => null, 'settings' => []]]
     */
    private function createFakeConfigurations(array $availableSuites)
    {
        return array_map(function () {
            return [
                'type' => null,
                'settings' => [],
            ];
        }, array_flip($availableSuites));
    }
}
