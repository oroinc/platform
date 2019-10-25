# JavaScript UnitTests

## Installation
For running JS tests following software required:
 - **[Node.js]** (JavaScript Engine)
 - **[Karma]** (Test Runner for JavaScript)
 - **[Jasmine 3.5]** (Behavior-Driven Development Testing Framework)

How to install **Node.js** you can find on [official website](https://nodejs.org/en/download/). 
After `node` is installed, you need to install several modules using **[Node Packaged Modules](https://npmjs.org/)** manager.
To do so, just execute following command from the root folder of your application:

```bash
npm install --prefix=vendor/oro/platform/build
```
Where `--prefix` parameter specifies relative path to `platform/build` directory.

## Configuration
Configuration for tests-run is placed in `build/karma.config.js.dist` (see [Karma documentation]).
Sometimes it's useful to create own configuration file, just copy `./vendor/oro/platform/build/karma.config.js.dist` file to `./vendor/oro/platform/build/karma.config.js` and modify it.

## Running
To run test call command
```bash
./vendor/oro/platform/build/node_modules/.bin/karma start ./vendor/oro/platform/build/karma.conf.js.dist --single-run
```

Don't forget to change the path to `platform/build` directory, if it is different in your application.

To run testsuite with custom configuration, you might find useful command line parameters which overwrites parameters in configuration file (see [documentation][Karma documentation]).

There are few custom options added for preparing karma config: 
- `--mask` _string_ file mask for Spec files. By default it is `'vendor/oro/**/Tests/JS/**/*Spec.js'`, that matches all Spec files in the project within oro vendor directory. 
- `--spec` _string_ path for certain Spec file, if it's passed the search by mask is skipped and test is run single Spec file.
- `--skip-indexing` _boolean_ allows to skip phase of collection Spec files and reuse the collection from previews run (if it exists).  
- `--theme` _string_ theme name is used to generate webpack config for certain theme. By default it is `'admin.oro'`.

For developer which use **PHPStorm** will be usefull couple extensions:
- **[Karma plugin]** helps to run testsuite from IDE and see result there; 
- **[ddescriber for jasmine]** helps quickly turn off or skip some tests from testsuite.

## Writing
How to write tests with **Jasmine 3.5** see [documentation][Jasmine 3.5].
Here's just trivial case, that's how look spec for `oroui/js/mediator` module:
```js
import mediator from 'oroui/js/mediator';
import Backbone from 'backbone';

describe('oroui/js/mediator', function () {
    it("compare mediator to Backbone.Events", function() {
        expect(mediator).toEqual(Backbone.Events);
        expect(mediator).not.toBe(Backbone.Events);
    });
});
```

<!--
### karma-jsmodule-exposure
This approach allows to test public API of a module. But what about mocking depended on modules and internal module's functional.

Here comes **[karma-jsmodule-exposure]** plugin. This **Karma**'s plugin on a fly injects exposing code inside js-module and provides API to manipulate internal variables.

See example how it works:
```js
import someModule from 'some/module';
import jsmoduleExposure from 'jsmodule-exposure';

// get exposure instance for tested module
var exposure = jsmoduleExposure.disclose('some/module');

describe('some/module', function () {
    var foo;

    beforeEach(function () {
        // create mock object with stub method 'do'
        foo = jasmine.createSpyObj('foo', ['do']);
        // before each test, pass it off instead of original
        exposure.substitute('foo').by(foo);
    });

    afterEach(function () {
        // after each test restore original value of foo
        exposure.recover('foo');
    });

    it('check doSomething() method', function() {
        someModule.doSomething();

        // stub method of mock object has been called
        expect(foo.do).toHaveBeenCalled();
    });
});

```
-->
### Jasmine-jQuery
[Jasmine-jQuery] extends base functionality of Jasmine:

 - adds bunch of useful matchers, allows to easily check state of a jQuery instance;
 - applies HTML-fixtures before each test and rolls back the document after tests.
 - provides the way for loading HTML and JSON fixtures required for a test;

But, due to `Jasmine-jQuery` requires full path to a fixture resource, it's better use `import` to perform loading fixtures by related path.

```js
import 'jasmine-jquery';
import $ from 'jquery';
import html from 'text-loader!./Fixture/markup.html';

describe('some/module', function () {
    beforeEach(function () {
        // appends loaded html to document's body,
        // after test rolls back it automatically
        window.setFixtures(html);
    });

    it('checks the markup of a page', function () {
        expect($('li')).toHaveLength(5);
    });
});
```

[Node.js]: <http://nodejs.org/>
[Karma]: <http://karma-runner.github.io/4.0/index.html>
[Karma documentation]: <http://karma-runner.github.io/4.0/config/configuration-file.html>
[Jasmine 3.5]: <hhttps://jasmine.github.io/api/3.5/global>
[Jasmine-jQuery]: <https://github.com/velesin/jasmine-jquery>
[karma-jsmodule-exposure]: <https://github.com/laboro/karma-jsmodule-exposure.git>
[Karma plugin]: <https://plugins.jetbrains.com/plugin/7287-karma>
[ddescriber for jasmine]: <https://plugins.jetbrains.com/plugin/7233-ddescriber-for-jasmine>
