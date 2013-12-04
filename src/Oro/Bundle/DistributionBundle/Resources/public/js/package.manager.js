function PackageManager(_installUrl, _uninstallUrl, _updateUrl) {

    var installUrl = _installUrl;
    var uninstallUrl = _uninstallUrl;
    var updateUrl = _updateUrl;

    var UninstallStatus = {UNINSTALLED: 0, ERROR: 1, CONFIRM: 2};
    var InstallStatus = {INSTALLED: 0, ERROR: 1, CONFIRM: 2};
    var UpdateStatus = {UPDATED: 0, ERROR: 1};

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
                break;
            case InstallStatus.ERROR:
                console.log('Error!');
                console.log(response.message);
                break;
            case InstallStatus.CONFIRM:
                console.log("Required packages: \n" + response.packages.join("\n"));
                console.log('Confirm');
                console.log('(new PackageManager()).install(' + JSON.stringify(response.params) + '); - will install everything required!');

                break;
            default:
                console.log('Unknown code');

        }

    }

    function uninstallCompleteCallback(xhr) {
        var response = xhr.responseJSON;
        console.log(response);

        switch (response.code) {
            case UninstallStatus.UNINSTALLED:
                console.log('Package uninstalled');
                break;
            case UninstallStatus.ERROR:
                console.log('Error!');
                console.log(response.message);
                break;
            case UninstallStatus.CONFIRM:
                console.log("Dependent packages: \n" + response.packages.join("\n"));
                console.log('Confirm');
                console.log('(new PackageManager()).uninstall(' + JSON.stringify(response.params) + '); - will uninstall everything dependent!');

                break;
            default:
                console.log('Unknown code');

        }
    }

    function updateCompleteCallback(xhr) {
        var response = xhr.responseJSON;
        console.log(response);

        switch (response.code) {
            case UpdateStatus.UNINSTALLED:
                console.log('Package uninstalled');
                break;
            case UpdateStatus.ERROR:
                console.log('Error!');
                console.log(response.message);
                break;
            default:
                console.log('Unknown code');

        }
    }

    return {
        install: function (params) {
            sendRequest(installUrl, params, installCompleteCallback);
        },
        uninstall: function (params) {
            sendRequest(uninstallUrl, params, uninstallCompleteCallback);
        },
        update: function (params) {
            sendRequest(updateUrl, params, updateCompleteCallback);
        }
    };
}