To provide backward compatibility for layout theme components, there are some unit tests which validates that 
layout elements in previous versions are not deleted/altered in a incompatible way in the current version.

Currently tested elements are:
- data providers: 
  - check the existence
  - provider methods existence
  - return type compatibility
  - method argument compatibility
- block types: 
  - check existence
  - check class reference
  - required options presence and default value
- context configurators:
  - check existence
  - check class reference
  - required options presence and default value
- storefront routes: check existence

To check these, we have: 
- Commands for reach layout element type to display the list of elements in the current app
  (`DebugDataProviderSignatureCommand`, `DebugLayoutBlockTypeSignatureCommand` etc.)
- `DumpSignaturesCommand` command (`oro:dump:layout:signatures`), which can execute all above exporter commands,
  and save them into yml files (`Tests/Functional/BC/ConfigFiles/{appVersion}/{element_type}_.yml`). 
  This command should be run on the previous version of the app (ex. 5.1 vs current 6.0), 
  then all the exported files should be copied into the next version (ex. `block_types.yml` copied into current 
  6.0 app's `Tests/Functional/BC/ConfigFiles/5.1/` )
- abstract `BaseLayoutBCTest`, which is extended by individual layout element tests (ex. `DataProviderBCTest`).
  They take every exported file, and compare with the current state of app. (ex. compare 5.1 files with current 6.0 list)

Note: When a test fails, careful analyses are required to find the reason an element was altered/removed, and decision
needs to be taken if modification is still allowed (then specific element should be marked as ignored in test).
