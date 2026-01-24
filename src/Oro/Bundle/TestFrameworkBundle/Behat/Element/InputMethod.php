<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

/**
 * Defines constants for different methods of inputting values into form elements.
 *
 * These constants represent the various strategies for setting values on form elements:
 * SELECT for dropdown selections, TYPE for keyboard input, and SET for direct value assignment.
 */
interface InputMethod
{
    const SELECT = 'select';
    const TYPE = 'type';
    const SET  = 'set';
}
