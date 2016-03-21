Action Groups
=============


What are the Action Groups?
---------------------------
Action Group is a named block of execution logic bunched together under its own `actions` configuration node. 
*Action groups* can be called with `@run_action_group` action in any application configuration nodes which supports Action Component.
*Action group* declaration also has important configuration section - `parameters` that describes all expected 
data from the caller (with type, requirement, default value, and validation message). 
Parameters will be accessible in actions as the root node of contextual data (e.g `$parameterName` or, internally, as `$.data.parameterName`). 
Also, along with `parameters` and `actions`, there can be optionally declared special `acl_resource` criteria and 
custom `conditions` node, where you can define special instructions to check against, before bunch execution process. 

ActionGroup Configuration
-------------------------

File `<bundleResourceRoot>/config/oro/action.yml`

```
action_groups:                                  # root node for action groups
    demo_flash_greetings_to:                   # name of action group
        parameters:                             # parameters declaration node
            what:                               # name of parameter
                required: false                 # (boolean, default = true) weather parameter is required or not
                type: AcmeBundle/String/Phrase  # (optional, default = any) type validation of parameter (available types: integer, string, boolean, array, double, object, PHP class)
                message: "Bad type"             # (optional) message to be prompted if parameter validation failure met
                default: "Hello"                # default value for optional parameter
            who: ~                              # set all defaults to parameter options (required: true, type: any) 
        conditions:                             # Condition expression
            @not_empty: [$who]
        actions:                                # list of actions that should be executed
            - @call_service_method:
                service: type_guesser
                method: guess
                method_parameters: [$who]       # as you can see, parameters are accessible from root $<parameterName>
                attribute: $.typeOfWho
            - @flash_message:
                message: "%param1%, %param2%!"
                type: 'info'
                message_parameters:
                    param1: $what
                    param2: $.typeOfWho
```

Now we can run this action_group with something like:

```
    @run_action_group:
        action_group: demo_flash_greetings_to
        parameters_mapping:
            who: $.myInstanceWithVariousType 
```
We are skipping `what` parameter, as it is not required and its `default` value is good for us.




