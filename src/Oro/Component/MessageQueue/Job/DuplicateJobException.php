<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * Exception thrown if a job with the same name already exists
 */
class DuplicateJobException extends \Exception
{
}
