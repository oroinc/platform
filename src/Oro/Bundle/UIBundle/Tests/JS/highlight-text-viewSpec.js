define(function(require) {
    'use strict';

    require('jasmine-jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var HighlightTextView = require('oroui/js/app/views/highlight-text-view');
    // fixtures
    var html = require('text!./Fixture/highlight-text-view.html');

    var createView = function(options) {
        if (createView.view) {
            createView.view.dispose();
        }

        window.setFixtures(html);
        createView.view = new HighlightTextView(_.defaults({}, {
            el: '#settings'
        }, options));

        return createView.view;
    };

    describe('oroui/js/app/views/highlight-text-view', function() {
        describe('check text highlight', function() {
            it('check highlight after initialize', function() {
                this.view = createView({
                    text: 'Group'
                });
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeFalsy();

                this.view = createView({
                    text: 'Group',
                    highlightSelectors: ['.group']
                });
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeTruthy();
            });

            it('check highlight after mediator event', function() {
                this.view = createView({
                    text: 'Groups',
                    highlightSelectors: ['.group']
                });
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeFalsy();

                mediator.trigger(':highlight-text:update', 'group');
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeTruthy();
            });

            it('check re-render highlight after mediator event change search value', function() {
                this.view = createView({
                    text: 'Groups',
                    highlightSelectors: ['.group']
                });
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeFalsy();

                mediator.trigger(':highlight-text:update', 'group');
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeTruthy();

                mediator.trigger(':highlight-text:update', '');
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeFalsy();
            });

            it('check highlight with not found text and fuzzy search', function() {
                this.view = createView({
                    text: 'Grp',
                    highlightSelectors: ['.group']
                });
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeFalsy();

                mediator.trigger(':highlight-text:update', 'Grp', true);
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeTruthy();
            });
        });

        describe('check elements toggle', function() {
            beforeEach(function() {
                this.view = createView({
                    highlightSelectors: ['.settings-title', '.group', '.field'],
                    toggleSelectors: {
                        '.field': '.fields',
                        '.group': '.settings-content'
                    }
                });
            });

            it('text not found', function() {
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeFalsy();
                expect(this.view.$(this.view.findNotFoundClass).length).toEqual(0);
                expect(this.view.$(this.view.findFoundClass).length).toEqual(0);
            });

            it('text found out of toggle elements', function() {
                mediator.trigger(':highlight-text:update', 'Settings');
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeTruthy();
                expect(this.view.$(this.view.findNotFoundClass).length).toEqual(0);
                expect(this.view.$(this.view.findFoundClass).length).toEqual(0);
            });

            it('text found in toggle elements', function() {
                mediator.trigger(':highlight-text:update', 'Group 2.Field 1');
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeTruthy();
                expect(this.view.$(':contains("Group 1")').hasClass(this.view.notFoundClass)).toBeTruthy();
                expect(this.view.$(':contains("Group 1.Field 1")').hasClass(this.view.notFoundClass)).toBeFalsy();
                expect(this.view.$(':contains("Group 1.Field 2")').hasClass(this.view.notFoundClass)).toBeFalsy();
                expect(this.view.$(':contains("Group 2")').hasClass(this.view.foundClass)).toBeTruthy();
                expect(this.view.$(':contains("Group 2.Field 1")').hasClass(this.view.foundClass)).toBeTruthy();
                expect(this.view.$(':contains("Group 2.Field 2")').hasClass(this.view.notFoundClass)).toBeTruthy();
            });

            it('text found in invisible elements', function() {
                mediator.trigger(':highlight-text:update', 'Group 2.Field 2.1');
                expect(this.view.$(this.view.findNotFoundClass).length).toEqual(0);
                expect(this.view.$(this.view.findFoundClass).length).toEqual(0);
            });
        });

        describe('check find text escaping special characters', function() {
            beforeEach(function() {
                this.view = createView({
                    highlightSelectors: ['.settings-title', '.group', '.field'],
                    toggleSelectors: {
                        '.field': '.fields',
                        '.group': '.settings-content'
                    }
                });
            });

            it('escaping special characters', function() {
                mediator.trigger(':highlight-text:update', '-\\[]{}()*+?.,\\^$#');
                expect(this.view.findText).toEqual(/\-\\\[\]\{\}\(\)\*\+\?\.\,\\\^\$\#/gi);
            });

            it('escaping mixed string', function() {
                mediator.trigger(':highlight-text:update', 'Grou#$p 2.F\ield 2*.1');
                expect(this.view.findText).toEqual(/Grou\#\$p 2\.Field 2\*\.1/gi);
            });
        });

        describe('check toggle trigger for changing state', function() {
            beforeEach(function() {
                this.view = createView({
                    highlightSelectors: ['.settings-title', '.group', '.field'],
                    toggleSelectors: {
                        '.field': '.fields',
                        '.group': '.settings-content'
                    },
                    highlightSwitcherContainer: '.toggle',
                    highlightStateStorageKey: 'show-all-configuration-items-on-search'
                });

                this.view.setHighlightSwitcherState(false);
            });

            afterEach(function() {
                window.localStorage.removeItem('show-all-configuration-items-on-search');
            });

            it('append toggle button container', function() {
                expect(this.view.$(this.view.highlightSwitcherElement).length).toEqual(1);
            });

            it('show all items', function() {
                mediator.trigger(':highlight-text:update', 'Group 2.Field 1');
                expect(this.view.isElementContentHighlighted(this.view.$el)).toBeTruthy();
                expect(this.view.$(':contains("Group 1")').hasClass(this.view.notFoundClass)).toBeTruthy();
                expect(this.view.$(':contains("Group 1.Field 1")').hasClass(this.view.notFoundClass)).toBeFalsy();
                expect(this.view.$(':contains("Group 1.Field 2")').hasClass(this.view.notFoundClass)).toBeFalsy();
                expect(this.view.$(':contains("Group 2")').hasClass(this.view.foundClass)).toBeTruthy();
                expect(this.view.$(':contains("Group 2.Field 1")').hasClass(this.view.foundClass)).toBeTruthy();
                expect(this.view.$(':contains("Group 2.Field 2")').hasClass(this.view.notFoundClass)).toBeTruthy();

                this.view.$(this.view.highlightSwitcherElement).trigger('click');

                expect(this.view.$(':contains("Group 1")').hasClass(this.view.notFoundClass)).toBeFalsy();
                expect(this.view.$(':contains("Group 1.Field 1")').hasClass(this.view.notFoundClass)).toBeFalsy();
                expect(this.view.$(':contains("Group 1.Field 2")').hasClass(this.view.notFoundClass)).toBeFalsy();
                expect(this.view.$(':contains("Group 2")').hasClass(this.view.foundClass)).toBeTruthy();
                expect(this.view.$(':contains("Group 2.Field 1")').hasClass(this.view.foundClass)).toBeTruthy();
                expect(this.view.$(':contains("Group 2.Field 2")').hasClass(this.view.notFoundClass)).toBeFalsy();

                expect(window.localStorage.getItem('show-all-configuration-items-on-search')).toEqual('true');
                this.view.setHighlightSwitcherState(false);
            });
        });
    });
});
