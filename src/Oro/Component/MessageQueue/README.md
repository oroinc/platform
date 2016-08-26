OroMessageQueue Component
=========================

The component incorporates message queue in your application via different transports.
It contains several layers.

The lowest layer is called Transport and provides an abstraction of transport protocol.
The Consumption layer provides tools to consume messages, such as cli command, signal handling, logging, extensions.
It works on top of transport layer.

The Client layer provides ability to start producing\consuming messages with as less as possible configuration.

Usage
-----

This is a complete example of message producing using only a transport layer:

```php
<?php

use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

$doctrineConnection = DriverManager::getConnection(
    ['url' => 'mysql://user:secret@localhost/mydb'],
    new Configuration
);

$connection = new DbalConnection($doctrineConnection, 'oro_message_queue');

$session = $connection->createSession();

$queue = $session->createQueue('aQueue');
$message = $session->createMessage('Something has happened');

$session->createProducer()->send($queue, $message);

$session->close();
$connection->close();
```

This is a complete example of message consuming using only a transport layer:

```php
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

$doctrineConnection = DriverManager::getConnection(
    ['url' => 'mysql://user:secret@localhost/mydb'],
    new Configuration
);

$connection = new DbalConnection($doctrineConnection, 'oro_message_queue');

$session = $connection->createSession();

$queue = $session->createQueue('aQueue');
$consumer = $session->createConsumer($queue);

while (true) {
    if ($message = $consumer->receive()) {
        echo $message->getBody();

        $consumer->acknowledge($message);
    }
}

$session->close();
$connection->close();
```

This is a complete example of message consuming using consumption layer:

```php
<?php
use Oro\Component\MessageQueue\Consumption\MessageProcessor;

class FooMessageProcessor implements MessageProcessor
{
    public function process(Message $message, Session $session)
    {
        echo $message->getBody();

        return self::ACK;
    }
}
```

```php
<?php
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Oro\Component\MessageQueue\Consumption\Extensions;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;

$doctrineConnection = DriverManager::getConnection(['url' => 'mysql://user:secret@localhost/mydb'], new Configuration);

$connection = new DbalConnection($doctrineConnection, 'oro_message_queue');

$queueConsumer = new QueueConsumer($connection, new Extensions([]));
$queueConsumer->bind('aQueue', new FooMessageProcessor());

try {
    $queueConsumer->consume();
} finally {
    $queueConsumer->getConnection()->close();
}
```

Jobs
----

Use jobs when your message flow has several steps(tasks) which run one after another.
Also jobs guaranty that job is unique i.e. you cant start new job with same name
until previous job has finished. Jobs have web gui where you can monitor jobs status
and interrupt jobs.

###Run only single job i.e. job with one step.

```php
class MessageProcessor implements MessageProcessorInterface
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            'oro:index:reindex',
            function (JobRunner $runner, Job $job) use ($data) {
                // do your job

                return true; // if you want to ACK message or false to REJECT
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}
```

###Job flow has 2 or more steps.

```php
class Step1MessageProcessor implements MessageProcessorInterface
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            'oro:index:reindex',
            function (JobRunner $runner, Job $job) use ($data) {
                // for example first step generates tasks for step two

                foreach ($entities as $entity) {
                    // every job name must be unique
                    $jobName = 'oro:index:index-single-entity:' . $entity->getId();
                    $runner->createDelayed($jobName, function (JobRunner $runner, Job $childJob) use ($entity) {
                        $this->producer->send('oro:index:index-single-entity', [
                            'entityId' => $entity->getId(),
                            'jobId' => $childJob->getId(),
                        ])
                    });
                }

                return true // if you want to ACK message or false to REJECT
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}

class Step2MessageProcessor implements MessageProcessorInterface
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        $result = $this->jobRunner->runDelayed(
            $data['jobId'],
            function (JobRunner $runner, Job $job) use ($data) {
                // do your job

                return true; // if you want to ACK message or false to REJECT
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}
```

###Dependent Job

Use dependent job when your job flow have several steps but you want to send new message
when all steps are finished.

```php
class MessageProcessor implements MessageProcessorInterface
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var DependentJobService
     */
    private $dependentJob;

    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            'oro:index:reindex',
            function (JobRunner $runner, Job $job) use ($data) {
                // register two dependent jobs
                // next messages will be sent to queue when that job and all children are finished
                $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
                $context->addDependentJob('topic1', 'message1');
                $context->addDependentJob('topic2', 'message2', MessagePriority::VERY_HIGH);

                $this->dependentJob->saveDependentJob($context);

                // do your job

                return true; // if you want to ACK message or false to REJECT
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}
```
