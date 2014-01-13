function PackageManager(Urls, util) {

    var UninstallStatus = {UNINSTALLED: 0, ERROR: 1, CONFIRM: 2};
    var InstallStatus = {INSTALLED: 0, ERROR: 1, CONFIRM: 2};
    var UpdateStatus = {UPDATED: 0, ERROR: 1};

    var reflectUICallback = function () {
    };

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
                var title = 'Confirm installation of ' + response.params.packageName;
                var message = '';
                message += "\n" + '<label>' +
                    ' <input type="checkbox" id="load-demo-data" checked="checked" />' +
                    '<span>Load demo data</span>' +
                    '</label>';

                if (response.requirements) {
                    var requirementsList = '';
                    for (var i = 0; i < response.requirements.length; i++) {
                        var r = response.requirements[i];
                        requirementsList += "\n - " + r.name;
                        requirementsList += r.installed ? ' [installed]' : '';
                    }
                    message += "\n";
                    message += response.params.packageName + ' requires following packages: ' +
                        requirementsList +
                        "\n\n" + 'All missing packages will be installed';
                }

                util.confirm(
                    title,
                    message,
                    function () {
                        var params = response.params;
                        params['loadDemoData'] = $('#load-demo-data').is(':checked') ? 1 : 0;

                        pm.install(params);
                    },
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
                    function () {
                        pm.uninstall(response.params)
                    },
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

    var pm = {
        install: function (params, _reflectUICallback) {
            reflectUICallback = _reflectUICallback || reflectUICallback;
            sendRequest(Urls.install, params, installCompleteCallback);
        },
        uninstall: function (params, _reflectUICallback) {
            reflectUICallback = _reflectUICallback || reflectUICallback;
            sendRequest(Urls.uninstall, params, uninstallCompleteCallback);
        },
        update: function (params, _reflectUICallback) {
            reflectUICallback = _reflectUICallback || reflectUICallback;
            sendRequest(Urls.update, params, updateCompleteCallback);
        }
    };

    return pm;
}
