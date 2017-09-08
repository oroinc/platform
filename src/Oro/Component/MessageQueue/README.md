OroMessageQueue Component
=========================

*Note:* This article is published in the Oro documentation library.

The component incorporates a message queue in your application via different transports.
It contains several layers.

The lowest layer is called Transport and provides an abstraction of transport protocol.
The Consumption layer provides tools to consume messages, such as cli command, signal handling, logging, extensions.
It works on top of transport layer.

The Client layer provides an ability to start producing/consuming messages with as little configuration as possible.

Table of Contents
-----------------
 - [External Links](#external-links)
 - [What is a Message Queue](#what-is-a-message-queue)
 - [Dictionary](#dictionary)
 - [Message Processors](#message-processors)
 - [Jobs](#jobs)
 - [Flow](#flow)
 - [Usage](#usage)
 
External Links
--------------
 - [What is Message Queue](http://www.ibm.com/support/knowledgecenter/SSFKSJ_9.0.0/com.ibm.mq.pro.doc/q002620_.htm)
 - [Message Queue Benefits](https://www.iron.io/top-10-uses-for-message-queue/) (most of them are applicable for Oro Message Queue Component)
 - [Rabbit MQ Introduction](https://www.rabbitmq.com/tutorials/tutorial-one-php.html)


What Is a Message Queue
-----------------------
Message queues provide an asynchronous communications protocol, meaning that the sender and the receiver of the message 
do not need to interact with the message queue at the same time. Messages placed onto the queue are stored until the 
recipient retrieves them. A message does not have information about previous and next messages. 

### Therefore, Message Queues Should Be Used If:

* A process can be executed asynchronously.
* A process does not affect User Experience.
* Processes need to be executed in parallel for faster performance.
* You need a guarantee of processing.
* You need scalability.

### Publish/Subscribe Messaging

OroMessageQueue uses *Publish/subscribe messaging*. It means that the sending application publishes (sends) a 
message with a specific *topic* and a *consumer* finds a subscriber(s) for the topic. Publish/subscribe messaging allows 
decoupling of the information provider from the consumers of that information. The sending application and the receiving 
application do not need to know anything about each other for the information to be sent and received.

Dictionary
----------

* **Message** - An information message which contains a *message topic* that indicates which *message processor(s)* will process it and a *message body* - array of parameters required for the processing, for example an entity id or a channel name. Messages are created and sent by a *message producer* and put to the "tail" of the *message queue*. When the message comes up, it is processed by a *consumer* using a *message processor*. Messages also contain a number of additional settings (see [Message settings](#message-settings)).
* **Message Queue** - A FIFO queue that holds *queue messages* until they are processed. There can be one or more queues. If we use only one queue, it is much easier. If there are several queues, it is much more difficult but more flexible sometimes. 
* **Consumer** - A component which takes messages from the queue and processes them. It processes one message at a time: once one message has finished being processed, the next message follows. For each message, the consumer runs a *message processor* subscribed to the *message topic* (if one exists). If there are several processors subscribed to the same topic, they can be run in parallel (actually messages are sent via broker and if the broker sees that a message has several receivers, it clones the message to create an individual message for each receiver). There can be more than one consumer and they can work on different servers. It can be done to increase the performance. When implementing a message processor, a developer should remember that *there can be several consumers working on different servers*.  
* **Message Processor** - Processes the queue messages (i.e. contains a code that should run when a consumer processes a message with the specified topic).
* **Message Topic** - Some identifier that indicates which message processor should be executed for the message. One processor can subscribe to several topics. Also, there can be several processes subscribed to the same topic.
* **Job** - A message processor can process a message directly or create a job. Jobs are created in the db and allow monitoring of the processes status, start and end time, interrupt processes. Also, if we split a process into a set of parallel processes, jobs allow monitoring and control of the whole set. See details in the [Jobs](#jobs) section. 

### Message Settings

* **Topic** - Refers to the term 'Message Topic' above.
* **Body** - A string or json encoded array with some data.
* **Priority** - Can be `MessagePriority::VERY_LOW`, `MessagePriority::LOW`, `MessagePriority::NORMAL`, `MessagePriority::HIGH`, `MessagePriority::VERY_HIGH`. Recognizing priority is simple: there are five queues, one queue per priority. Consumers process messages from the VERY_HIGH queue. If there are no messages in the VERY_HIGH queue, consumers process messages from the HIGH queue, etc. Consequently, if all other queues are empty, consumer processes messages from the VERY_LOW queue.
* **Expire** - A number of seconds after which the message should be removed from the queue without processing.
* **Delay** - A number of seconds that the message should be delayed for before it is sent to a queue.


Message Processors
------------------

**Message Processors** are classes that process queue messages. They implement `MessageProcessorInterface`. In addition, they usually subscribe to the specific topics and implement `TopicSubscriberInterface`.

A `process(MessageInterface $message, SessionInterface $session)` method describes the actions that should be performed when
a message is received. It can perform the actions directly or create a job. It can also produce a new message to run another
processor asynchronously.

### Processing Status

The received message can be processed, rejected, and re-queued. An exception can also be thrown.

**Message Processor will return `self::ACK` in the following cases:**

* If a message wass processed successfully.
* If the created job returned `true`.

It means that the message was processed successfully and is removed from the queue.

**Message Processor will return `self::REJECT` in the following cases:**

* If a message is broken.
* If the created job returned `false`. 

It means that the message was not processed and is removed from the queue because it is unprocessable and will never become processable (e.g. a required parameter is missing or another permanent error appears).

**There could be two options:**

* The message became unprocessable as a result of normal work. For example, when the message was sent to an the entity that existed at the moment of sending, but somebody deleted it. The entity will not appear again and we can reject the message. It is normal workflow, so user intervention is not required.
* The message became unprocessable due a failure. For example, when an entity id was invalid or missing. This is abnormal behavior, the message should also be rejected, but the processor requires user attention (e.g. log a critical error or even throw an exception).

**If a message cannot be processed temporarily**, for example, in case of connection timeout due server overload,  the `process`
method should return `self::REQUEUE`. The message will be returned to the queue again and will be processed later.
**This will also happen if an exception is thrown during processing or job running**.

**The workflow of re-queuing messages (processor returns `self::REQUEUE`) is the following:**
 
1. A consumer processes a message (runs the `process` method of the message processor).
2. The `process` method returns `self::REQUEUE`.
3. The consumer puts the message (i.e. a copy of the message) to the end of the queue setting the `redelivery` flag to true. 
4. The consumer continues message processing (the requeued message is at the end of the queue).
5. When the turn of the requeued message comes, the `DelayRedeliveredMessageExtension` works and sets a delay for the requeued message.
6. The time set in the delay passes and the message is processed again.

**The workflow of re-queuing messages when an exception is thrown inside a message processor is slightly different:**

1. A consumer processes a message (runs `process` method of the message processor).
2. An exception is thrown inside the `process` method.
3. The consumer logs the exception and puts the message (i.e. a copy of the message) to the end of the queue setting the `redelivery` flag to true. Then the consumer fails with the exception.
4. The consumer should be re-run at this stage. It can be done manually or automatically with an utility like [supervisord](http://supervisord.org/). Manual re-run is preferred for developing as developers should review the exceptions thrown on the message processing. Automatic re-run is preferred for regression testing or prod.
5. The consumer continues message processing (the failing message is at the end of the queue).
6. When the turn of the failing message comes, the `DelayRedeliveredMessageExtension` works and sets a delay for the failing message.
7. After the delay time passes, the message is processed again and the consumer can fail again.

### Example

A processor receives a message with the entity id. It finds the entity and changes its status without creating any job.

```php
    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        
        if (! isset($body['id'])) {
            $this->logger->critical(
                sprintf('Got invalid message, id is empty: "%s"', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }
        
        $em = $this->getEntityManager();
        $repository = $em->getRepository(SomeEntity::class);
        
        $entity = $repository->find($body['id']);
        
        if(! $entity) {
            $this->logger->error(
                sprintf('Cannot find an entity with id: "%s"', $body['id']),
                ['message' => $message]
            );

            return self::REJECT;            
        }
        
        $entity->setStatus('success');
        $em->persist($entity);
        $em->flush();
        
        return self::ACK;
      }

```

Overall, there can be three cases:

* The processor received a message with an entity id. The entity was found. The process method of the processor changed the entity status and returned self::ACK.
* The processor received a message with an entity id. The entity was not found. This is possible if the entity was deleted when the message was in the queue (i.e. after it was sent but before it was processed). This is expected behavior, but the processor rejects the message because the entity does not exist and will not appear later. Please note that we use error logging level.
* The processor received a message with an empty entity id. This is unexpected behavior. There are definitely bugs in the code that sent the message. We also reject the message but using critical logging level to inform that user intervention is required. 


Jobs
----

A message processor can be implemented with or without creating jobs. 

There is no ideal criteria to help decide whether a job should be created or not. A developer should decide each time which approach is better in this case.

Here are a few recommendations:

#### We Can Skip a Job Creation If:

* We have an easy fast-executing action such as status changing etc.
* Our action looks like an event listener.

#### We should always create jobs if:

* The action is complicated and can be executed for a long time.
* We need to monitor execution status.
* We need to run an unique job i.e. do not allow running a job with the same name until the previous job has finished.
* We need to run a step-by-step action i.e. the message flow has several steps (tasks) which run one after another.
* We need to split a job for a set of sub-jobs to run in parallel and monitor the status of the whole task. 

Jobs are usually run with JobRunner.

### JobRunner

JobRunner creates and runs a job. Usually one of the following methods is used:

#### runUnique

`public function runUnique($ownerId, $name, \Closure $runCallback)`

Runs the `$runCallback`. It does not allow another job with the same name to be run at the same time.


#### createDelayed

`public function createDelayed($name, \Closure $startCallback)`

A sub-job which runs asynchronously (sending its own message). It can only run inside another job.

#### runDelayed

`public function runDelayed($jobId, \Closure $runCallback)`

This method is used inside a processor for a message which was sent with createDelayed. 

The `$runCallback` closure usually returns `true` or `false`, the job status depends on the returned value.
See [Jobs statuses](#jobs-statuses) section for the details.

To reuse existing processor logic in scope of job it may be decorated with `DelayedJobRunnerDecoratingProcessor` which will execute
runDelayed, pass control to given processor and then handle result in format applicable for `runDelayed`  

### A Dependent Job

Use a dependent job when your job flow has several steps but you want to send a new message
when all steps are finished.

In the example below, a root job is created. As soon as its work is completed,
it sends two messages with the 'topic1' and 'topic2' topics  to the queue.


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

                // some work to do

                return true; // if you want to ACK message or false to REJECT
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}
```

The dependant jobs can be added only to root jobs (i.e. to the jobs created with `runUnique`, not `runDelayed`). 

### Jobs Structure

A two-level job hierarchy is created for the process where:

* A root job can have a few child jobs.
* A child job can have one root job.
* A child job cannot have child jobs of its own.
* A root job cannot have a root job of its own.

  
* If we use just `runUnique`, then a parent and a child jobs with the same name are created.
* If we use `runUnique` and `createDelayed` inside it, then a parent and a child job for `runUnique` are created. Then each run of `createDelayed` adds another child for the runUnique parent.


### Job Statuses

* **Single job:** When a message is being processed by a consumer and a JobRunner method `runUnique` is called without creating any child jobs:
  * The root job is created and the closure passed in params runs. The job gets `Job::STATUS_RUNNING` status, the job `startedAt` field is set to the current time.
  * If the closure returns `true`, the job status is changed to `Job::STATUS_SUCCESS`, the job `stoppedAt` field is changed to the current time.
  * If the closure returns `false` or throws an exception, the job status is changed to `Job::STATUS_FAILED`, the job `stoppedAt` field is changed to the current time.
  * If someone interrupts the job, it stops working and gets `Job::STATUS_CANCELLED` status, the job `stoppedAt` field is changed to the current time.
* **Child jobs:** When a message is being processed by a consumer, a JobRunner method `runUnique` is called which creates child jobs with `createDelayed`:
  * The root job is created and the closure passed in params runs. The job gets  `Job::STATUS_RUNNING` status, the job `startedAt` field is set to the current time.
  * When the JobRunner method `createDelayed` is called, the child jobs are created and get the `Job::STATUS_NEW` statuses. The messages for the jobs are sent to the message queue.
  * When a message for a child job is being processed by a consumer and a JobRunner method `runDelayed` is called, the closure runs and the child jobs get `Job::STATUS_RUNNING` status.
  * If the closure returns `true`, the child job status is changed to `Job::STATUS_SUCCESS`, the job `stoppedAt` field is changed to the current time.
  * If the closure returns `false` or throws an exception, the child job status is changed to `Job::STATUS_FAILED`, the job `stoppedAt` field is changed to the current time.
  * When all child jobs are stopped, the root job status is changed according to the child jobs statuses.
  * If someone interrupts a child job, it stops working and gets `Job::STATUS_CANCELLED` status, the job `stoppedAt` field is changed to the current time.
  * If someone interrupts the root job, the child jobs that are already running finish their work and get the statuses according to the work result (see the description above). The child jobs that are not run yet are cancelled and get `Job::STATUS_CANCELLED` statuses.
* **Also:** If a jobs closure returns `true`, the process method which runs this job should return `self::ACK`. If a job closure returns `false`, the process method which runs this job should return `self::REJECT`.
  

Flow
----

### Simple flow

Usually the message flow looks the following way:

![Simple Message Flow](./Resources/doc/simple_message_flow.png "Simple Message Flow")

However, if there are more than one processor subscribed to the same topic, the flow becomes more complicated. The client's message producer sends a message to a router message processor. It takes the message and searches for real recipients that are interested in such message. Then it sends a copy of the message to all of them. Each target message processor takes its copy of the message and processes it.

### Simple Way to Run Several Processes in Parallel

Let us imagine that we want to run two processes in parallel. In this case, we can create a Processor B with the 
first process and Processor C with the second process. We can then create Processor A, inject Message 
Producer into it, and send messages to Processor B and Processor C. The messages are put to the queue 
and when their turn comes, the consumers run processes B and C. That could be done in parallel.
 
![Simple Parallel Process Running Flow](./Resources/doc/simple_parallel_processes_running.png "Simple Parallel Process Running Flow")

Code example:

```php

    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        if ({$message is invalid}) {
            $this->logger->critical(
                sprintf('Got invalid message: "%s"', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        foreach ($data['ids'] as $id) {
            $this->producer->send(Topics::DO_SOMETHING_WITH_ENTITY, [
                'id' => $id,
                'targetClass' => $data['targetClass'],
                'targetId' => $data['targetId'],
            ]);
        }

        $this->logger->info(sprintf(
            'Sent "%s" messages',
            count($data['ids'])
        ));

        return self::ACK;
    }
```

The processor in the example accepts an array of some entity ids and sends a message `Topics:DO_SOMETHING_WITH_ENTITY` 
to each id. The messages are put to the message queue and will be processed when their turn comes. It could be done in parallel if several consumers are running.

The approach is simple and works perfectly well, although it has a few flaws.

* We do not have a way to **monitor** the **status** of processes except for reading log files. In the example above we do not know how many entities are being processed and how many are still in the queue. We also do not know how many entities were processed successfully and how many received errors during the processing. 
* We cannot ensure the **unique** run.   
* We cannot easily **interrupt** the running processes. 

### Flow to Run Parallel Jobs via Creating a Root Job and Child Jobs Using runUnique/createDelayed/runDelayed

This way of running parallel jobs is more appropriate than the previous one, although it is slightly more complicated. It is, however, the preferred way for the parallel processes implementation.

The task is the same as the previous one. We want to run two processes in parallel. We are also creating processors 
A, B and C but they are slightly different.

We inject JobRunner to *Processor A*. Inside the `process` method, it runs `runUnique` method. In the closure
of the `runUnique`, it runs `createDelayed` method for *Processor B* and for *Processor C* passing `jobId` param to its closure.
Inside the closures of `createDelayed`, the messages for *Processor B* and *Processor C* are created and sent.
We should also add the `jobId` params to the message bodies except for the required params.

Processors B and C are also slightly different. Their process methods call `runDelayed` method passing the received 
`jobId` param. 

The benefits are the following:

* **Unique running**. As we use `runUnique` method in Processor A, a new instance of it cannot run until the previous instance completes all the jobs.
* **Jobs are created in the db**. A root job is created for Processor A and child jobs are added to it for Processors B and C. 
* **Status monitoring**. We can see the statuses of all the child jobs: *new* for just created, *running* for the jobs that are currently running, *success* for the jobs that finished successfully and *failed* for the jobs that failed.
* A root job status is *running* until all child jobs are finished.
* **Interrupt**. We can interrupt a child job or a root job. If we interrupt a root job, all its running child jobs complete their work. Child jobs that have not started will not start.

![Running Parallel Jobs - a Root Job with async Sub-jobs](./Resources/doc/running_parallel_jobs.png "Running Parallel Jobs")

#### Example of createDelayed and runDelayed Usage

The processor subscribes to `Topics::DO_BIG_JOB` and runs a unique big job (the name of the job is Topics::DO_BIG_JOB - the same as the topic name so it will not be possible to run another big job at the same time) 
The processor creates a set of delayed jobs, each of them sends `Topics::DO_SMALL_JOB` message.

```php
    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $bigJobParts = JSON::decode($message->getBody());

        $result = $this->jobRunner->runUnique( //a root job is creating here 
            $message->getMessageId(),
            Topics::DO_BIG_JOB,
            function (JobRunner $jobRunner) use ($bigJobParts) {

                foreach ($bigJobParts as $smallJob) {
                    $jobRunner->createDelayed( // child jobs are creating here and get new status
                        sprintf('%s:%s', Topics::DO_SMALL_JOB, $smallJob),
                        function (JobRunner $jobRunner, Job $child) use ($smallJob) {
                            $this->producer->send(Topics::DO_SMALL_JOB, [ // messages for child jobs are sent here
                                'smallJob' => $smallJob,
                                'jobId' => $child->getId(), // the created child jobs ids are passing as message body params
                            ]);
                        }
                    );
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
```

The processor subscribes to the `Topics::DO_SMALL_JOB` and runs the created delayed job.

```php
    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $payload = JSON::decode($message->getBody());

        $result = $this->jobRunner->runDelayed($payload['jobId'], function (JobRunner $jobRunner) use ($payload) {
            //the child job status with the id $payload['jobId'] is changed from new to running
            
            $smallJobData = $payload['smallJob'];
            
            if (! $this->checkDataValidity($smallJobData))) {
                $this->logger->error(
                    sprintf('Invalid data received: "%s"', $smallJobData),
                    ['message' => $payload]
                );

                return false; //the child job status with the id $payload['jobId'] is changed from running to failed
            }

            return true; //the child job status with the id $payload['jobId'] is changed from running to success
        });

        return $result ? self::ACK : self::REJECT;
    }
```

A root job is created for the big job and a set of its child jobs is created for the small jobs. 

More Examples
-------------

### Run Only a Single Job (i.e. Job with One Step with runUnique)

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

### Job Flow Has Two or More Steps  

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
                    $runner->createDelayed(
                        $jobName,
                        function (JobRunner $runner, Job $childJob) use ($entity) {
                            $this->producer->send('oro:index:index-single-entity', [
                                'entityId' => $entity->getId(),
                                'jobId' => $childJob->getId(),
                            ])
                    });
                }

                return true; // if you want to ACK message or false to REJECT
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


Usage
-----

The following is an example of a message producing using only a transport layer:

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

The following is an example of a message consuming using only a transport layer:

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

The following is an example of a message consuming using consumption layer:

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
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;

$doctrineConnection = DriverManager::getConnection(
    ['url' => 'mysql://user:secret@localhost/mydb'],
    new Configuration
);

$connection = new DbalConnection($doctrineConnection, 'oro_message_queue');

$queueConsumer = new QueueConsumer($connection, new ChainExtension([]));
$queueConsumer->bind('aQueue', new FooMessageProcessor());

try {
    $queueConsumer->consume();
} finally {
    $queueConsumer->getConnection()->close();
}
```
