<?php

namespace Oro\Bundle\FormBundle\Form\Exception;

use Symfony\Component\Form\Exception\ExceptionInterface;

/**
 * Thrown when an error occurs during form operations.
 *
 * This exception is used to signal errors specific to form handling, such as
 * invalid form configuration, data transformation failures, or validation issues.
 * It implements Symfony's {@see ExceptionInterface} for consistency with the form component.
 */
class FormException extends \Exception implements ExceptionInterface
{
}
