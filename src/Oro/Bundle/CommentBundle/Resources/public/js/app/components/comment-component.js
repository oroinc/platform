/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CommentComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        routing = require('routing'),
        tools = require('oroui/js/tools'),
        mediator = require('oroui/js/mediator'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        CommentListView = require('orocomment/js/app/views/comment-list-view'),
        CommentCollection = require('orocomment/js/app/models/comment-collection');
        //CommentModel = require('orocomment/js/app/models/comment-list-model'),
        //CommentFormView = require('orocomment/js/app/views/comment-form-view');

    CommentComponent = BaseComponent.extend({
        /*defaults: {
            commentListOptions: {
                configuration: {},
                urls: {
                    getList: '/app_dev.php/api/rest/latest/commentlist/'
                },
                routes: {},
                pager: {},
                itemView:  CommentView,
                itemModel: CommentModel,
                defaultPage: 0
            },
            commentListData: '[]',
            commentListCount: 0,
            widgetId: '',
            modules: {},
            form: {
                url: '/app_dev.php/comments/form',
                isLoaded: false,
                view: '',
                contentHTML: ''
            }
        },*/

        initialize: function (options) {
            var collectionOptions;

            this.options = options || {};
            collectionOptions = _.pick(this.options, ['relatedEntityId', 'relatedEntityClassName', 'formName']);

            this.collection = new CommentCollection([], collectionOptions);

            this.view = new CommentListView({
                el: options._sourceElement,
                collection: this.collection,
                template: options.listTemplate
            });

            this.collection.fetch();
            //var model = this.collection.add({message: 'test'});
            //debugger;
//debugger;
            /*this.processOptions();

            this.loadForm();

            this.loadComments();

            if (!_.isEmpty(this.options.modules)) {
                this._deferredInit();
                tools.loadModules(this.options.modules, function (modules) {
                    _.extend(this.options.commentListOptions, modules);
                    this.initView();
                    this._resolveDeferredInit();
                }, this);
            } else {
                this.loadModules();
                this.initView();
            }*/
        }
        /*processOptions: function () {
            var defaults;
            defaults = $.extend(true, {}, this.defaults);
            _.defaults(this.options, defaults);
            _.defaults(this.options.commentListOptions, defaults.commentListOptions);
        },
        loadForm: function () {
            var that = this;

            $.ajax({url: this.options.form.url, type: 'get', dataType: 'html'})
                .done(function(data) {
                    that._setCommentForm(data);

                    that.options.form.view = new CommentFormView({
                        el: that.options._sourceElement,
                        contentHTML: that.options.form.contentHTML
                    });
                })
                .fail(_.bind(that._showLoadItemsError, this));
        },
        loadComments: function () {
            var that = this;

            $.ajax({url: this._genCommentUrl(), type: 'get', dataType: 'html'})
                .done(function(data) {
                    console.log(data);
                })
                .fail(_.bind(that._showLoadItemsError, this));

        },
        initView: function() {
            var commentOptions, collection;
            commentOptions = this.options.commentListOptions;

            // setup comment list collection
            collection = new CommentCollection(this.options.commentListOptions, {
                model: commentOptions.itemModel
            });

            collection.route = activityOptions.urls.route;
            collection.routeParameters = activityOptions.urls.parameters;
            collection.setPageSize(this.options.activityListOptions.pager.pagesize);
            collection.setCount(this.options.activityListCount);

            activityOptions.collection = collection;

            // bind template for item view
            activityOptions.itemView = activityOptions.itemView.extend({
                template: _.template($(activityOptions.itemTemplate).html())
            });

            this.list = new ActivityListView(activityOptions);

            this.registerWidget();
        },
        _showLoadItemsError: function (err) {
            this._showError(this.options.messages.loadItemsError, err);
        },
        _setCommentForm: function(form) {
            this.options.form.contentHTML = form;
        },
        _genCommentUrl: function () {
            return this.options.commentListOptions.urls.getList
                + this.options.activityClassName
                + '/'
                + this.options.activityId
                + '/'
                + this.options.commentListOptions.defaultPage
                + '.json'
                ;
        }*/
    });


    return CommentComponent;
});
