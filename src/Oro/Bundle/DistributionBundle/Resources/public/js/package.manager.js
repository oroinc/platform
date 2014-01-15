function PackageManager(Urls, util) {

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
                util.redirect(Urls.installed, 'Package installed successfully');

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
                        requirementsList += r.installed ? ' <span class="installed">[installed]</span>' : '';
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

    function updateCompleteCallback(xhr) {
        var response = xhr.responseJSON;

        switch (response.code) {
            case UpdateStatus.UPDATED:
                util.redirect(Urls.installed, 'Package updated successfully');

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
        update: function (params, _reflectUICallback) {
            reflectUICallback = _reflectUICallback || reflectUICallback;
            sendRequest(Urls.update, params, updateCompleteCallback);
        }
    };

    return pm;
}
