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
        console.log(response);

        switch (response.code) {
            case InstallStatus.INSTALLED:
                console.log('Package installed');
//                util.success('Package installed');
                util.redirect(Urls.installed, 'Package installed');

                break;
            case InstallStatus.ERROR:
                console.log('Error!');
                console.log(response.message);
                util.error(response.message);
                reflectUICallback();

                break;
            case InstallStatus.CONFIRM:
                console.log("Required packages: \n" + response.packages.join("\n"));
                console.log('Confirm');
                console.log('pm.install(' + JSON.stringify(response.params) + '); - will install everything required!');
                var message = response.params.packageName + ' requires following packages: ' +
                    "\n" + response.packages.join("\n") +
                    "\n" + "\n" + 'Do you want to install them all?';

                util.confirm(
                    message,
                    function(){pm.install(response.params)},
                    'Yes, install',
                    reflectUICallback
                );
                break;
            default:
                console.log('Unknown code');
                util.error('Unknown error');
                reflectUICallback();
        }


    }

    function uninstallCompleteCallback(xhr) {
        var response = xhr.responseJSON;
        console.log(response);

        switch (response.code) {
            case UninstallStatus.UNINSTALLED:
                console.log('Package uninstalled');
                util.redirect(Urls.installed, 'Package uninstalled');

                break;

            case UninstallStatus.ERROR:
                console.log('Error!');
                console.log(response.message);
                util.error(response.message);
                reflectUICallback();

                break;

            case UninstallStatus.CONFIRM:
                console.log("Dependent packages: \n" + response.packages.join("\n"));
                console.log('Confirm');
                console.log('pm.uninstall(' + JSON.stringify(response.params) + '); - will uninstall everything dependent!');
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
                console.log('Unknown code');
                util.error('Unknown error');
                reflectUICallback();

        }
    }

    function updateCompleteCallback(xhr) {
        var response = xhr.responseJSON;
        console.log(response);

        switch (response.code) {
            case UpdateStatus.UPDATED:
                console.log('Package updated');
                util.redirect(Urls.installed, 'Package updated');

                break;

            case UpdateStatus.ERROR:
                console.log('Error!');
                console.log(response.message);
                util.error(response.message);

                break;

            default:
                console.log('Unknown code');
                util.error('Unknown error');
        }

        reflectUICallback();
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
