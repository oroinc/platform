<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Content;

use Oro\Bundle\NavigationBundle\Content\SimpleTagGenerator;

class SimpleTagGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var  SimpleTagGenerator */
    protected $generator;

    public function setUp()
    {
        $this->generator = new SimpleTagGenerator();
    }

    public function tearDown()
    {
        unset($this->generator);
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param mixed $data
     * @param bool  $expectedResult
     */
    public function testSupports($data, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->generator->supports($data));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'simple array given'               => [['name' => 'tagSimpleName'], true],
            'given array with name and params' => [['name' => 'tagSimpleName', 'params' => ['das']], true],
            'given empty array w/o name'       => [[], false],
            'given string'                     => ['testString', false],
            'given object'                     => [new \StdClass(), false]
        ];
    }

    /**
     * @dataProvider generateDataProvider
     *
     * @param mixed $data
     * @param bool  $includeCollectionTag
     * @param int   $expectedCount
     */
    public function testGenerate($data, $includeCollectionTag, $expectedCount)
    {
        $result = $this->generator->generate($data, $includeCollectionTag);
        $this->assertCount($expectedCount, $result);
    }

    /**
     * @return array
     */
    public function generateDataProvider()
    {
        return [
            'should return tags by name param'                                  => [['name' => 'testName'], false, 1],
            'should return tags by name param and params'                       => [
                ['name' => 'testName', 'params' => ['test']],
                false,
                1
            ],
            'should return tags by name param with collection data '            => [['name' => 'testName'], true, 2],
            'should return tags by name param and params with collection data ' =>
                [['name' => 'testName', 'params' => ['test']], true, 2],
        ];
    }

    public function testGenerateIncludesParams()
    {
        $tagWOParams = ['name' => 'testName'];

        $result        = $this->generator->generate($tagWOParams);
        $tagWithParams = $tagWOParams + ['params' => ['activeSection']];
        $this->assertNotEquals(
            $result,
            $this->generator->generate($tagWithParams),
            'Should generate tag depends on given params'
        );
    }
}
