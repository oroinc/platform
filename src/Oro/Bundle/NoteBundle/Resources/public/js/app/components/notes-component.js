import BaseComponent from 'oroui/js/app/components/base/component';
import $ from 'jquery';
import _ from 'underscore';
import routing from 'routing';
import loadModules from 'oroui/js/app/services/load-modules';
import mediator from 'oroui/js/mediator';
import NoteView from '../views/note-view';
import NotesView from '../views/notes-view';
import NoteModel from '../models/note-model';
import NotesCollection from '../models/notes-collection';

const NotesComponent = BaseComponent.extend({
    defaults: {
        notesOptions: {
            urls: {},
            routes: {},
            itemView: NoteView,
            itemModel: NoteModel
        },
        notesData: '[]',
        widgetId: '',
        modules: {}
    },

    /**
     * @inheritdoc
     */
    constructor: function NotesComponent(options) {
        NotesComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        options = options || {};
        this.processOptions(options);

        if (!_.isEmpty(options.modules)) {
            this._deferredInit();
            loadModules(options.modules, function(modules) {
                _.extend(options.notesOptions, modules);
                this.initView(options);
                this._resolveDeferredInit();
            }, this);
        } else {
            this.initView(options);
        }
    },

    processOptions: function(options) {
        const defaults = $.extend(true, {}, this.defaults);
        _.defaults(options, defaults);
        _.defaults(options.notesOptions, defaults.notesOptions);

        // map item routes to action url function
        _.each(options.notesOptions.routes, function(route, name) {
            options.notesOptions.urls[name + 'Item'] = function(model) {
                return routing.generate(route, {id: model.get('id')});
            };
        });

        delete options.notesOptions.routes;
        options.notesData = JSON.parse(options.notesData);
        options.notesOptions.el = options._sourceElement;

        // collect modules which should be loaded before initialization
        _.each(['itemView', 'itemModel'], function(name) {
            if (typeof options.notesOptions[name] === 'string') {
                options.modules[name] = options.notesOptions[name];
            }
        });
    },

    initView: function(options) {
        const notesOptions = options.notesOptions;

        // setup notes collection
        const collection = new NotesCollection(options.notesData, {
            model: notesOptions.itemModel
        });
        collection.baseUrl = notesOptions.urls.list;
        notesOptions.collection = collection;

        // bind template for item view
        notesOptions.itemView = notesOptions.itemView.extend({// eslint-disable-line oro/named-constructor
            template: _.template($(notesOptions.itemTemplate).html())
        });

        this.list = new NotesView(notesOptions);
        this.registerWidget(options);
    },

    registerWidget: function(options) {
        const list = this.list;
        mediator.execute('widgets:getByIdAsync', options.widgetId, function(widget) {
            widget.getAction('expand_all', 'adopted', function(action) {
                action.on('click', list.expandAll.bind(list));
            });
            widget.getAction('collapse_all', 'adopted', function(action) {
                action.on('click', list.collapseAll.bind(list));
            });
            widget.getAction('refresh', 'adopted', function(action) {
                action.on('click', list.refresh.bind(list));
            });
            widget.getAction('toggle_sorting', 'adopted', function(action) {
                action.on('click', list.toggleSorting.bind(list));
            });
        });
    }
});

export default NotesComponent;
