# OroWorkflowBundle

OroWorkflowBundle enables developers to incorporate business processes into the Oro applications by defining and managing conditional sequences of entity transformations in Workflows and Processes YAML configuration files.

It contains two important features, workflows and processes.

*A workflow* is a complex solution that allows users to perform a set of actions with predefined conditions -
each next action depends on the previous one. Usually, workflows are used to manage a specific entity and to create additional related entities.

*Processes* provide the possibility to automate tasks related to entity management. They use the main doctrine events to perform described tasks at the right time. Each process can be performed immediately or after a timeout. Processes use the OroMessageQueue component and the bundle to provide the possibility of delayed execution.

## Workflow Transition Flow

```mermaid
flowchart TD
    A(("Transition")) -- Is Precondition Allowed --> B["pre_announce"]
    B -- not allowed --> X("end")
    B --> C["Check Preconditions"]
    C --> announce["announce"]
    announce -- not allowed --> X
    announce -- Is Conditions Allowed --> pre_guard["pre_guard"]
    pre_guard -- not allowed --> X
    pre_guard --> F["Check Conditions"]
    F --> guard["guard"]
    guard -- not allowed --> X
    LS["leave"] --> SE["enter"]
    SE --> CS["Do Change Step"]
    CS --> SED["entered"]
    SED -- transit --> transit["transit"]
    transit --> ET["Execute Transition Logic"]
    ET --> completed["completed"]
    completed --> X & n3["Is Final Step"]
    guard -- Change Step --> n1["Workflow Started?"]
    n1 -- Yes --> LS
    n1 -- No --> n2["start"]
    n2 --> SE
    n3 -- No --> X
    n4["finish"] --> X
    n3 -- Yes --> n4
    C@{ shape: hex}
    F@{ shape: hex}
    CS@{ shape: hex}
    ET@{ shape: hex}
    n3@{ shape: diam}
    n1@{ shape: diam}
    n2@{ shape: rect}
    n4@{ shape: rect}
    style C stroke:#424242,fill:#424242,color:#FFFFFF
    style F fill:#424242,color:#FFFFFF
    style CS fill:#424242,color:#FFFFFF
    style ET fill:#424242,color:#FFFFFF
```

See [website documentation](https://doc.oroinc.com/backend/entities-data-management/workflows/) for more details.

