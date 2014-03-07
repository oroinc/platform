<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Model\ReminderState;

class ReminderStateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ReminderState
     */
    protected $reminderState;

    /**
     * @dataProvider typesProvider
     */
    public function testCreate($types)
    {
        $this->reminderState = new ReminderState();
    }

    public function typesProvider()
    {
        return [
            'empty' => [
                'types' => ['type1' => true, 'type2' => false],
            ],
            'not_empty' => [
                'types' => ['type1' => true, 'type2' => false],
            ],
        ];
    }
}
