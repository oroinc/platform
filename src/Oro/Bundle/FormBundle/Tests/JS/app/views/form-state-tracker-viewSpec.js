import 'jasmine-jquery';
import $ from 'jquery';
import pageStateChecker from 'oronavigation/js/app/services/page-state-checker';
import FormStateTrackerView from 'oroform/js/app/views/form-state-tracker-view';

describe('oroform/js/app/views/form-state-tracker-view', function() {
    let view;

    function createView(options) {
        return new FormStateTrackerView(Object.assign({el: '#tracker-form'}, options));
    }

    beforeEach(function() {
        spyOn(pageStateChecker, 'registerChecker');
        spyOn(pageStateChecker, 'removeChecker');
    });

    afterEach(function() {
        if (view && !view.disposed) {
            view.dispose();
        }
        view = null;
        FormStateTrackerView.registry = {};
    });

    describe('captureInitialState', function() {
        it('captures form inputs as name-value pairs', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="firstName" value="John"/>' +
                    '<input type="text" name="lastName" value="Doe"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            expect(view.initialState).toEqual([
                {name: 'firstName', value: 'John'},
                {name: 'lastName', value: 'Doe'}
            ]);
        });
    });

    describe('hasChanges', function() {
        it('returns false when no initial state has been captured', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="value"/>' +
                '</form>'
            );
            view = createView();

            expect(view.hasChanges()).toBe(false);
        });

        it('returns false when form state matches initial state', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="value"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            expect(view.hasChanges()).toBe(false);
        });

        it('returns true when an input value has changed', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="original"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            view.$('input[name="field"]').val('modified');

            expect(view.hasChanges()).toBe(true);
        });

        it('returns true when a new input is added', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field1" value="a"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            view.$el.append('<input type="text" name="field2" value="b"/>');

            expect(view.hasChanges()).toBe(true);
        });

        it('returns false after the view is disposed', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="value"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();
            view.dispose();

            expect(view.hasChanges()).toBe(false);
        });
    });

    describe('IGNORE filter', function() {
        it('excludes button, submit, reset, password, and file inputs', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="tracked" value="yes"/>' +
                    '<input type="button" name="btn" value="click"/>' +
                    '<input type="submit" name="sub" value="go"/>' +
                    '<input type="reset" name="rst" value="clear"/>' +
                    '<input type="password" name="pwd" value="secret"/>' +
                    '<input type="file" name="doc"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            expect(view.initialState.length).toBe(1);
            expect(view.initialState[0].name).toBe('tracked');
        });

        it('excludes _token fields', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="val"/>' +
                    '<input type="hidden" name="form[_token]" value="abc123"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            expect(view.initialState.length).toBe(1);
            expect(view.initialState[0].name).toBe('field');
        });

        it('excludes inputs inside [data-ignore-form-state-change] containers', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="tracked" value="val"/>' +
                    '<div data-ignore-form-state-change>' +
                        '<input type="text" name="ignored" value="skip"/>' +
                    '</div>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            expect(view.initialState.length).toBe(1);
            expect(view.initialState[0].name).toBe('tracked');
        });

        it('excludes temp-validation-name inputs', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="val"/>' +
                    '<input type="text" name="temp-validation-name-123" value="tmp"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            // temp-validation-name inputs are excluded from the IGNORE selector
            // and also filtered out during comparison in isDifferentFromInitialState
            const names = view.initialState.map(item => item.name);
            expect(names).not.toContain('temp-validation-name-123');
        });
    });

    describe('select2 inputs', function() {
        let originalInputWidget;

        beforeEach(function() {
            originalInputWidget = $.fn.inputWidget;
        });

        afterEach(function() {
            if (originalInputWidget) {
                $.fn.inputWidget = originalInputWidget;
            } else {
                delete $.fn.inputWidget;
            }
        });

        it('uses inputWidget value for hidden select2 inputs', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="hidden" class="select2" name="product" value="42"/>' +
                '</form>'
            );

            $.fn.inputWidget = jasmine.createSpy('inputWidget').and.callFake(function(method) {
                if (method === 'data') {
                    return [{id: 42, text: 'Product A'}];
                }
            });

            view = createView();
            view.captureInitialState();

            expect(view.initialState.length).toBe(1);
            expect(view.initialState[0].name).toBe('product');
            expect(view.initialState[0].value).toBe('42');
        });
    });

    describe('resetInitialState', function() {
        it('clears the initial state', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="val"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            expect(view.initialState).not.toBeNull();

            view.resetInitialState();

            expect(view.initialState).toBeNull();
        });

        it('causes hasChanges to return false after reset', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="original"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();
            view.$('input[name="field"]').val('changed');

            expect(view.hasChanges()).toBe(true);

            view.resetInitialState();

            expect(view.hasChanges()).toBe(false);
        });
    });

    describe('patchInitialState', function() {
        it('replaces matching fields by name sequence', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="a" value="1"/>' +
                    '<input type="text" name="b" value="2"/>' +
                    '<input type="text" name="c" value="3"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            // Change value of "b" in the DOM
            view.$('input[name="b"]').val('updated');

            // Patch the initial state with the updated element
            view.patchInitialState(view.$('input[name="b"]'));

            expect(view.initialState[1]).toEqual({name: 'b', value: 'updated'});
        });

        it('inserts new fields at the correct position based on neighbors', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="a" value="1"/>' +
                    '<input type="text" name="c" value="3"/>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            // Add a new input between a and c
            view.$('input[name="a"]').after('<input type="text" name="b" value="2"/>');

            view.patchInitialState(view.$('input[name="b"]'));

            expect(view.initialState.length).toBe(3);
            expect(view.initialState[0]).toEqual({name: 'a', value: '1'});
            expect(view.initialState[1]).toEqual({name: 'b', value: '2'});
            expect(view.initialState[2]).toEqual({name: 'c', value: '3'});
        });

        it('does nothing when initialState is null', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="a" value="1"/>' +
                '</form>'
            );
            view = createView();

            // initialState is null, patchInitialState should be a no-op
            view.patchInitialState(view.$('input[name="a"]'));

            expect(view.initialState).toBeNull();
        });

        it('does nothing when $elem contains no trackable inputs', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="a" value="1"/>' +
                    '<div id="empty-container"></div>' +
                '</form>'
            );
            view = createView();
            view.captureInitialState();

            const stateBefore = view.initialState.slice();
            view.patchInitialState(view.$('#empty-container'));

            expect(view.initialState).toEqual(stateBefore);
        });
    });

    describe('pageStateChecker integration', function() {
        it('registers checker on initialize', function() {
            window.setFixtures('<form id="tracker-form"></form>');
            view = createView();

            expect(pageStateChecker.registerChecker).toHaveBeenCalledWith(view.hasChanges);
        });

        it('removes checker on dispose', function() {
            window.setFixtures('<form id="tracker-form"></form>');
            view = createView();

            const hasChangesFn = view.hasChanges;
            view.dispose();

            expect(pageStateChecker.removeChecker).toHaveBeenCalledWith(hasChangesFn);
        });
    });

    describe('dispose', function() {
        it('sets disposed flag', function() {
            window.setFixtures('<form id="tracker-form"></form>');
            view = createView();
            view.dispose();

            expect(view.disposed).toBe(true);
        });

        it('does not throw when called twice', function() {
            window.setFixtures('<form id="tracker-form"></form>');
            view = createView();
            view.dispose();

            expect(function() {
                view.dispose();
            }).not.toThrow();
        });
    });

    describe('autoCapture option', function() {
        it('captures initial state automatically when autoCapture is true', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="auto"/>' +
                '</form>'
            );
            view = createView({autoCapture: true});

            expect(view.initialState).not.toBeNull();
            expect(view.initialState).toEqual([
                {name: 'field', value: 'auto'}
            ]);
        });

        it('does not capture initial state when autoCapture is false', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="manual"/>' +
                '</form>'
            );
            view = createView({autoCapture: false});

            expect(view.initialState).toBeNull();
        });

        it('does not capture initial state by default', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="default"/>' +
                '</form>'
            );
            view = createView();

            expect(view.initialState).toBeNull();
        });
    });

    describe('group registry', function() {
        it('registers view in the default group by default', function() {
            window.setFixtures('<form id="tracker-form"></form>');
            view = createView();

            expect(FormStateTrackerView.registry['default']).toContain(view);
        });

        it('registers view in a custom group', function() {
            window.setFixtures('<form id="tracker-form"></form>');
            view = createView({group: 'draftOrder'});

            expect(FormStateTrackerView.registry.draftOrder).toContain(view);
            expect(FormStateTrackerView.registry['default']).toBeUndefined();
        });

        it('removes view from registry on dispose', function() {
            window.setFixtures('<form id="tracker-form"></form>');
            view = createView({group: 'testGroup'});

            expect(FormStateTrackerView.registry.testGroup).toContain(view);

            view.dispose();

            expect(FormStateTrackerView.registry.testGroup).toBeUndefined();
        });

        it('cleans up empty group key on dispose', function() {
            window.setFixtures('<form id="tracker-form"></form>');
            view = createView({group: 'cleanupGroup'});
            view.dispose();

            expect(FormStateTrackerView.registry.hasOwnProperty('cleanupGroup')).toBe(false);
        });

        it('keeps group in registry when other views remain', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="a" value="1"/>' +
                '</form>'
            );
            const view1 = createView({group: 'shared'});
            const view2 = new FormStateTrackerView({el: '#tracker-form', group: 'shared'});

            expect(FormStateTrackerView.registry.shared.length).toBe(2);

            view1.dispose();

            expect(FormStateTrackerView.registry.shared.length).toBe(1);
            expect(FormStateTrackerView.registry.shared).toContain(view2);

            view2.dispose();

            expect(FormStateTrackerView.registry.shared).toBeUndefined();
            view = null;
        });
    });

    describe('hasChangesInGroup', function() {
        it('returns false when group does not exist', function() {
            expect(FormStateTrackerView.hasChangesInGroup('nonexistent')).toBe(false);
        });

        it('returns false when no tracker in group has changes', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="val"/>' +
                '</form>'
            );
            view = createView({group: 'testGroup'});
            view.captureInitialState();

            expect(FormStateTrackerView.hasChangesInGroup('testGroup')).toBe(false);
        });

        it('returns true when a tracker in group has changes', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="original"/>' +
                '</form>'
            );
            view = createView({group: 'testGroup'});
            view.captureInitialState();

            view.$('input[name="field"]').val('modified');

            expect(FormStateTrackerView.hasChangesInGroup('testGroup')).toBe(true);
        });

        it('does not report changes from different group', function() {
            window.setFixtures(
                '<form id="tracker-form">' +
                    '<input type="text" name="field" value="original"/>' +
                '</form>'
            );
            view = createView({group: 'groupA'});
            view.captureInitialState();

            view.$('input[name="field"]').val('modified');

            expect(FormStateTrackerView.hasChangesInGroup('groupB')).toBe(false);
        });
    });
});
