Processes Documentation
==============================

Table of Contents
-----------------
 - [What is Processes?](#what-is-workflow)
 - [Main Entities](#main-entities)
 - [How it works?](#how-it-works)
 - [Configuration](#configuration)
 - [Console commands](#console-commands)
 - [REST API](#rest-api)

What is Processes?
------------------

Processes provide possibility for automate tasks related to entity management. They are using main doctrine events
to perform described tasks at the right time. Each process can be performed immediately or after some timeout.
Processes used JMS Job bundle to provide possibility of delayed startup.

Main Entities
-------------

Processes are represented by three entities.

* **Definition** - main entity that contain information about specific process. It contains the most important
information: process related entity type (f.e. user) and what the actions must be performed with this entity
(f.e. change value of some field). Another important option is execution order that affects order of processes
execution if several process are subscribed on the same event of the same entity. Process can be enabled or disabled.
Other fields of the process definition contain information about process name, when this process has been created and
when it has been updated last time.

* **Trigger** - entity provides information about the trigger used to run the related process when
this process will be invoked. First parameter is trigger event - one of ``create``, ``update`` or ``delete``;
second parameter defines entity field name used to listen (used for ``update`` event only) and  process will be invoked
only if value of this field has been changed. Also trigger contains information about when process
should be performed - immediately or after some delay (and delay interval in the seconds of PHP date interval format).

* **Job** - entity that contain information specific to performing process in case of delayed processing
(in that case JMS job will be created). According to event job will contain following data:
    - ``create`` event - entity identity will be saved;
    - ``update`` event - entity identity and change set (old and new values) will be saved;
    - ``delete`` event - entity plain fields (without references) will be saved.
Also each job entity contains relation to the trigger that was used and entity hash (full class name
of the related entity and identity of the specific entity). This entity hash is needed to find all registered jobs
for the same specific entity.

How it works?
-------------

Each of process definition related to the some entity type (i.e. consists full class name) and each definition
can have several triggers.

When user performs some action with entity which is related to some enabled process definition,
then all existing triggers for this process will be analyzed and found appropriate ones to execute.

There are two ways how trigger can be processed. First is immediate execution - in this case process action will be
executed right after entity will be flushed to the database. Second is delayed execution - it creates job and puts it
to queue. If some entity has several appropriate process triggers, then all of them will be processed
in order defined by definition.

After the specific entity item is deleted all job processes related to this entity also will be deleted.

**Attention:** performing of the action that was described in the process definition can provoke triggers
of other processes (or even same process). Please use appropriate condition to avoid recursion.
This problem will be fixed in future.

Configuration
-------------

All processes are described in configuration. Look at the example of simple process configuration that performs
some action with Contact entity.

```
definitions:                                                 # list of definitions
    contact_definition:                                      # name of process definition
        label: 'Contact Definition'                          # label of the process definition
        enabled: true                                        # this definition is enabled (activated)
        entity: OroCRM\Bundle\ContactBundle\Entity\Contact   # related entity
        order: 20                                            # processing order
        actions_configuration:                               # list of actions to perform
            - @find_entity:                                  # find existing entity
                conditions:                                  # action conditions
                    @empty: $assignedTo                      # if field $assignedTo is empty
                parameters:                                  # action parameters
                    class: Oro\Bundle\UserBundle\Entity\User # $assignedTo entity full class name
                    attribute: $assignedTo                   # name of attribute that will store entity
                    where:                                   # where conditions
                        username: 'admin'                    # username is 'admin'
triggers:                                                    # list of triggers
    contact_definition:                                      # name of trigger
        -
            event: create                                    # event on which the trigger performed
        -
            event: update                                    # event on which the trigger performed
            field: assignedTo                                # field name to listen
            queued: true                                     # this process must be executed in queue
            time_shift: 60                                   # this process must be executed with 60 seconds delay
```

This configuration describes process that relates to the ``Contact`` entity and every time when any contact will be
created or  ``Assigned To`` field will be changed, then current administrator user will be set as assigned.
In other words contact will be assigned to the current administrator.

This logic is implemented using one definition and two triggers.
First trigger will be processed immediately after the contact is be created, and second one creates new process job
and put it to JMS queue, so job will be processed after ``60`` seconds delay.

**Note:** If you want to test this process configuration in real application, you can put this configuration in file
``Oro/Bundle/WorkflowBundle/Resources/config/process.yml`` and reload definitions using console command
``app/console oro:process:configuration:load`` - after that you can create ``Contact`` of changed assigned user
and ensure that process works.

Console commands
----------------

WorkflowBundle provides two following console commands to work with processes.

### oro:process:configuration:load

This command loads processes configuration from *.yml configuration files to the database. It used
during application installation and update. Command has two optional options:
    - **--directories** - specifies directories used to find configuration files (multiple values allowed);
    - **--definitions** - specifies names of the process definitions that should be loaded (multiple values allowed).

### oro:process:execute:job

This command simply executes process job with specified identifier. It used in the JMS jobs to execute delayed
processes. Command has one required option:
    - **--id** - identifier of the process job to execute.

REST API
--------

OroWorkflowBundle provides REST API that allows activation and deactivation of processes.

Activation URL attributes:
* **route:** ``oro_workflow_api_rest_process_activate``
* **parameter:** workflowDefinition - name of the appropriate process definition

Deactivation URL attributes:
* **route:** ``oro_workflow_api_rest_process_deactivate``
* **parameter:** workflowDefinition - name of the appropriate process definition
