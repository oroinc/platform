import _ from 'underscore';
import errorHelper from 'oroui/js/error';
import mediator from 'oroui/js/mediator';
import __ from 'orotranslation/js/translator';

const entityStructureErrorHandler = {
    _showErrorMessage: _.throttle(function() {
        errorHelper.showErrorInUI(__('oro.entity.not_exist'));
    }, 100, {trailing: false}),

    /**
     * @param {EntityError} error
     */
    handle: function(error) {
        entityStructureErrorHandler._showErrorMessage();
        errorHelper.showErrorInConsole(error);
    },

    handleOutdatedDataError: _.throttle(function() {
        mediator.execute('showMessage', 'warning', __('oro.entity.structure_outdated.message'));
    }, 100, {trailing: false})
};

export default entityStructureErrorHandler;
