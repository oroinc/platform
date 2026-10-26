import 'jasmine-jquery';
import $ from 'jquery';
import 'oroui/js/input-widget-manager';
import DependentFieldComponent from 'oroform/js/app/components/dependent-field-component';

describe('oroform/js/app/components/dependent-field-component', () => {
    let components;

    const createComponent = id => {
        const component = new DependentFieldComponent({_sourceElement: $(`#${id}`)});
        components.push(component);

        return component;
    };
    const isRowHidden = id => $(`#${id}`).closest('.control-group').hasClass('hide');
    const toggle = (id, checked) => $(`#${id}`).prop('checked', checked).trigger('change');
    const select = (id, value) => $(`#${id}`).val(value).trigger('change');

    beforeEach(() => {
        components = [];
    });

    afterEach(() => {
        components.forEach(component => component.dispose());
    });

    describe('checkbox dependee', () => {
        beforeEach(() => {
            window.setFixtures(`
                <form>
                    <div class="control-group">
                        <input type="checkbox" id="first" data-dependee-id="first" checked>
                    </div>
                    <div class="control-group">
                        <input type="checkbox" id="shown" data-depend-on="first" data-show-if="checked">
                    </div>
                    <div class="control-group">
                        <input type="checkbox" id="hidden" data-depend-on="first" data-hide-if="checked">
                    </div>
                </form>
            `);
        });

        it('shows a field when "data-show-if" matches the dependee', () => {
            createComponent('shown');

            expect(isRowHidden('shown')).toBeFalse();
        });

        it('hides a field when the dependee stops matching "data-show-if"', () => {
            createComponent('shown');

            toggle('first', false);

            expect(isRowHidden('shown')).toBeTrue();
        });

        it('hides a field when "data-hide-if" matches the dependee', () => {
            createComponent('hidden');

            expect(isRowHidden('hidden')).toBeTrue();

            toggle('first', false);

            expect(isRowHidden('hidden')).toBeFalse();
        });

        it('stops following the dependee after dispose', () => {
            const component = createComponent('shown');
            component.dispose();

            toggle('first', false);

            expect(isRowHidden('shown')).toBeFalse();
        });
    });

    describe('select dependee', () => {
        beforeEach(() => {
            window.setFixtures(`
                <form>
                    <div class="control-group">
                        <select id="mode" data-dependee-id="mode" multiple>
                            <option value="a">a</option>
                            <option value="b">b</option>
                            <option value="c">c</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <input type="text" id="any" data-depend-on="mode" data-show-if="a | b & c">
                    </div>
                    <div class="control-group">
                        <input type="text" id="cleared" value="text" data-depend-on="mode" data-show-if="a"
                            data-clear-if="b" data-disable-element="input">
                    </div>
                </form>
            `);
        });

        it('evaluates "|" and "&" in "data-show-if"', () => {
            createComponent('any');

            select('mode', ['a']);
            expect(isRowHidden('any')).toBeFalse();

            select('mode', ['b']);
            expect(isRowHidden('any')).toBeTrue();

            select('mode', ['b', 'c']);
            expect(isRowHidden('any')).toBeFalse();
        });

        it('disables the "data-disable-element" inputs of a hidden field', () => {
            select('mode', ['c']);
            createComponent('cleared');

            expect($('#cleared').prop('disabled')).toBeTrue();

            select('mode', ['a']);

            expect($('#cleared').prop('disabled')).toBeFalse();
        });

        it('clears the field when "data-clear-if" matches and restores it afterwards', () => {
            select('mode', ['a']);
            createComponent('cleared');

            select('mode', ['b']);
            expect($('#cleared').val()).toBe('');

            select('mode', ['a']);
            expect($('#cleared').val()).toBe('text');
        });
    });

    describe('chain of dependent fields', () => {
        beforeEach(() => {
            window.setFixtures(`
                <form>
                    <div class="control-group">
                        <input type="checkbox" id="first" data-dependee-id="first" checked>
                    </div>
                    <div class="control-group">
                        <input type="checkbox" id="second" data-dependee-id="second" data-depend-on="first"
                            data-show-if="checked" checked>
                    </div>
                    <div class="control-group">
                        <input type="checkbox" id="third" data-depend-on="second" data-show-if="checked">
                    </div>
                    <div class="control-group">
                        <input type="text" id="cleared" value="text" data-depend-on="second" data-show-if="checked"
                            data-clear-if="unchecked">
                    </div>
                </form>
            `);
        });

        it('shows the last field when every field in the chain is checked', () => {
            createComponent('second');
            createComponent('third');

            expect(isRowHidden('third')).toBeFalse();
        });

        it('hides the last field when the first field is unchecked', () => {
            createComponent('second');
            createComponent('third');

            toggle('first', false);

            expect(isRowHidden('second')).toBeTrue();
            expect(isRowHidden('third')).toBeTrue();
        });

        it('shows the last field again when the first field is checked again', () => {
            createComponent('second');
            createComponent('third');
            toggle('first', false);

            toggle('first', true);

            expect(isRowHidden('third')).toBeFalse();
        });

        it('hides the last field when it is initialized before the field it depends on', () => {
            $('#first').prop('checked', false);
            createComponent('third');
            createComponent('second');

            expect(isRowHidden('third')).toBeTrue();
        });

        it('restores the cleared field after the first field is toggled in between', () => {
            createComponent('second');
            createComponent('cleared');
            toggle('second', false);

            toggle('first', false);
            toggle('first', true);
            toggle('second', true);

            expect($('#cleared').val()).toBe('text');
        });

        it('does not clear the field when it is initialized before the field it depends on', () => {
            $('#second').prop('checked', false);
            createComponent('cleared');
            createComponent('second');

            expect($('#cleared').val()).toBe('text');
        });
    });
});
