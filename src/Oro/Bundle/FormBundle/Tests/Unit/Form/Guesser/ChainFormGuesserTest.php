<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Guesser;

use Oro\Bundle\FormBundle\Guesser\ChainFormGuesser;
use Oro\Bundle\FormBundle\Guesser\FormBuildData;

class ChainFormGuesserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChainFormGuesser
     */
    protected $chainGuesser;

    protected function setUp()
    {
        $this->chainGuesser = new ChainFormGuesser();
    }

    protected function tearDown()
    {
        unset($this->chainGuesser);
    }

    public function testAddGuesser()
    {
        $firstGuesser = $this->getMockForAbstractClass('Oro\Bundle\FormBundle\Guesser\FormGuesserInterface');
        $secondGuesser = $this->getMockForAbstractClass('Oro\Bundle\FormBundle\Guesser\FormGuesserInterface');

        $this->chainGuesser->addGuesser($firstGuesser);
        $this->chainGuesser->addGuesser($secondGuesser);
    }

    /**
     * @param string|null $expected
     * @param bool $firstGuess
     * @param bool $secondGuess
     * @dataProvider guessDataProvider
     */
    public function testGuess($expected, $firstGuess = false, $secondGuess = false)
    {
        $entity = 'Test/Entity';
        $field = 'testField';

        $firstGuesser = $this->getMockForAbstractClass('Oro\Bundle\FormBundle\Guesser\FormGuesserInterface');
        $firstGuesser->expects($this->once())->method('guess')->with($entity, $field)
            ->will($this->returnValue($firstGuess ? new FormBuildData('firstGuess') : null));

        $secondGuesser = $this->getMockForAbstractClass('Oro\Bundle\FormBundle\Guesser\FormGuesserInterface');
        if ($firstGuess) {
            $secondGuesser->expects($this->never())->method('guess');
        } else {
            $secondGuesser->expects($this->once())->method('guess')->with($entity, $field)
                ->will($this->returnValue($secondGuess ? new FormBuildData('secondGuess') : null));
        }

        $this->chainGuesser->addGuesser($firstGuesser);
        $this->chainGuesser->addGuesser($secondGuesser);
        $formBuildData = $this->chainGuesser->guess($entity, $field);

        if ($expected) {
            $this->assertInstanceOf('Oro\Bundle\FormBundle\Guesser\FormBuildData', $formBuildData);
            $this->assertEquals($expected, $formBuildData->getFormType());
        } else {
            $this->assertNull($formBuildData);
        }
    }

    public function guessDataProvider()
    {
        return array(
            'not guessed' => array(
                'expected' => null,
                'firstGuess' => false,
                'secondGuess' => false,
            ),
            'first guessed' => array(
                'expected' => 'firstGuess',
                'firstGuess' => true,
                'secondGuess' => true,
            ),
            'second guessed' => array(
                'expected' => 'secondGuess',
                'firstGuess' => false,
                'secondGuess' => true,
            ),
        );
    }
}
