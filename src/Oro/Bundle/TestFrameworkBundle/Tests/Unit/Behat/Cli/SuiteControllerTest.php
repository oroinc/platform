<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli;

use Behat\Testwork\Suite\Generator\GenericSuiteGenerator;
use Behat\Testwork\Suite\Suite;
use Behat\Testwork\Suite\SuiteRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Cli\SuiteController;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;

class SuiteControllerTest extends \PHPUnit_Framework_TestCase
{
    private $suites = [
        'One',
        'Two',
        'Three',
        'Four',
        'Five',
        'Six',
    ];

    /**
     * @dataProvider applicableSuitesProvider
     * @param array $applicableSuites
     */
    public function testApplicableSuites(array $applicableSuites)
    {
        $suiteRegistry = new SuiteRegistry();
        $suiteRegistry->registerSuiteGenerator(new GenericSuiteGenerator());
        $controller = new SuiteController(
            $suiteRegistry,
            $this->createFakeConfigurations($this->suites),
            $applicableSuites
        );

        $input = new InputStub('', [], ['applicable-suites' => true]);
        $controller->execute($input, new OutputStub());

        self::assertEquals($applicableSuites, $this->getRegisteredSuiteNames($suiteRegistry));
    }

    /**
     * @expectedException \Behat\Testwork\Suite\Exception\SuiteNotFoundException
     * @expectedExceptionMessage `Unregistered` suite is not found or has not been properly registered.
     */
    public function testSuiteNotFountException()
    {
        $suiteRegistry = new SuiteRegistry();
        $suiteRegistry->registerSuiteGenerator(new GenericSuiteGenerator());
        $controller = new SuiteController(
            $suiteRegistry,
            $this->createFakeConfigurations($this->suites),
            ['One', 'Two', 'Unregistered']
        );

        $input = new InputStub('', [], ['applicable-suites' => true]);
        $controller->execute($input, new OutputStub());
    }

    /**
     * @return array
     */
    public function applicableSuitesProvider()
    {
        return [
            [[
                'Three',
                'Four',
                'Five',
            ]],
            [[
                'Two',
                'Three',
                'Five',
                'Six',
            ]],
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

    /**
     * @param SuiteRegistry $suiteRegistry
     * @return array of suites names
     */
    private function getRegisteredSuiteNames(SuiteRegistry $suiteRegistry)
    {
        return array_map(function (Suite $suite) {
            return $suite->getName();
        }, $suiteRegistry->getSuites());
    }
}
