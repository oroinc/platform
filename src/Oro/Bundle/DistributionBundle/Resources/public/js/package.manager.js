function PackageManager(Urls, util) {

    var UninstallStatus = {UNINSTALLED: 0, ERROR: 1, CONFIRM: 2};
    var InstallStatus = {INSTALLED: 0, ERROR: 1, CONFIRM: 2};
    var UpdateStatus = {UPDATED: 0, ERROR: 1};

    var reflectUICallback = function(){};

    function sendRequest(url, params, completeCallback) {
        $.ajax({
            url: url,
            method: 'GET',
            data: {params: params},
            dataType: 'json',
            complete: completeCallback
        });
    }

    function installCompleteCallback(xhr) {
        var response = xhr.responseJSON;

        switch (response.code) {
            case InstallStatus.INSTALLED:
                util.redirect(Urls.installed, 'Package installed');

                break;
            case InstallStatus.ERROR:
                util.error(response.message);
                reflectUICallback();

                break;
            case InstallStatus.CONFIRM:
                var title = 'Confirm installation of '+response.params.packageName;
                var message = response.params.packageName + ' requires following packages: ' +
                    "\n - " + response.packages.join("\n -") +
                    "\n" + "\n" + 'All missing packages will be installed';

                util.confirm(
                    title,
                    message,
                    function(){pm.install(response.params)},
                    'Continue',
                    reflectUICallback
                );
                break;
            default:
                util.error('Unknown error');
                reflectUICallback();
        }


    }

    function uninstallCompleteCallback(xhr) {
        var response = xhr.responseJSON;

        switch (response.code) {
            case UninstallStatus.UNINSTALLED:
                util.redirect(Urls.installed, 'Package uninstalled');

                break;

            case UninstallStatus.ERROR:
                util.error(response.message);
                reflectUICallback();

                break;

            case UninstallStatus.CONFIRM:
                var message = 'Following packages depend on ' +
                    response.params.packageName + ':' +
                    "\n" + "\n" + response.packages.join("\n") +
                    "\n" + "\n" + 'Do you want to uninstall them all?';
                util.confirm(
                    message,
                    function(){pm.uninstall(response.params)},
                    'Yes, delete',
                    reflectUICallback
                );

                break;

            default:
                util.error('Unknown error');
                reflectUICallback();

        }
    }

    function updateCompleteCallback(xhr) {
        var response = xhr.responseJSON;

        switch (response.code) {
            case UpdateStatus.UPDATED:
                util.redirect(Urls.installed, 'Package updated');

                break;

            case UpdateStatus.ERROR:
                util.error(response.message);
                reflectUICallback();

                break;

            default:
                util.error('Unknown error');
                reflectUICallback();
        }
    }

    var pm= {
        install: function (params, _reflectUICallback) {
            reflectUICallback=_reflectUICallback || reflectUICallback;
            sendRequest(Urls.install, params, installCompleteCallback);
        },
        uninstall: function (params, _reflectUICallback) {
            reflectUICallback=_reflectUICallback || reflectUICallback;
            sendRequest(Urls.uninstall, params, uninstallCompleteCallback);
        },
        update: function (params, _reflectUICallback) {
            reflectUICallback=_reflectUICallback || reflectUICallback;
            sendRequest(Urls.update, params, updateCompleteCallback);
        }
    };

    return pm;
}
