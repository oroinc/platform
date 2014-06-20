OroWorkflowBundle
=================

In the scope of this bundle existed two useful features,
to wit - [workflow](./Resources/doc/reference/workflow/index.md) and [processes](./Resources/doc/reference/processes/index.md)

Workflow is a complex solution that allows user to perform set of actions with predefined conditions -
each next action depends on previous. Also Workflow can be described as some kind of wizard that helps user
to perform complex actions. Usually Workflow is used to manage some specific entity and to create additional
related entities.

Processes provide possibility for automate tasks related to entity management. They are used main doctrine events
to perform described tasks at the right time. Each process can be performed immediately or after some timeout.
Processes used JMS Job bundle to provide possibility of delayed startup tasks.

Please see [documentation](./Resources/doc/index.md) for more details.
