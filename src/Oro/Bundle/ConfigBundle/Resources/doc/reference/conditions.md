Conditions
==========

Table of Contents
-----------------
 - [Is System Config Equal Condition](#is-system-config-equal-condition)


Is System Config Equal Condition
--------------------------------

**Class:** Oro\Bundle\ConfigBundle\Condition\IsSystemConfigEqual

**Alias:** is_system_config_equal

**Description:** Check that System Configuration has needed value

**Parameters:**
  - key - configuration key of stored value;
  - value - compared value;

**Configuration Example**
```
- @is_system_config_equal: ['some_config_path', 'needed value']

```
