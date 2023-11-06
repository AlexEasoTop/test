document.addEventListener("DOMContentLoaded", function() {
    const submitForm = document.querySelector('#submit');

    const checkIP = (event) => {
        event.preventDefault();
        let ip = document.querySelector('input[name="ip"]').value;

        let geoDataDiv = document.querySelector('.geodata');
        let errorText = document.querySelector('.error-text');

        geoDataDiv.innerHTML = "";
        errorText.innerHTML = "";
        geoDataDiv.classList.add('hidden');
        errorText.classList.add('hidden');

        if (!ip) {
            errorText.classList.remove('hidden');
            errorText.insertAdjacentHTML('beforeend', '<p>Empty input</p>');
            return false;
        }

        BX.ajax.runComponentAction('testtask:ip.check', 'check', {
            mode: 'class',
            data: {ip: ip}
        }).then(function(response) {
            let prepareGeoData = '<p>IP: '+ response.data.IP + '</p>' +
                                 '<p>Регион: '+ response.data.region + '</p>' +
                                 '<p>Город: '+ response.data.city + '</p>' +
                                 '<p>Код ОКАТО: '+ response.data.okato + '</p>' +
                                 '<p>Почтовый индекс: '+ response.data.post + '</p>' +
                                 '<p>Координаты: '+ response.data.coords + '</p>';
            geoDataDiv.classList.remove('hidden');
            geoDataDiv.insertAdjacentHTML('beforeend', prepareGeoData);
        }, function (response) {
            errorText.classList.remove('hidden');
            response.errors.forEach(error => {
                errorText.insertAdjacentHTML('beforeend', '<p>' + error.message + '</p>');
            });
        });
    }


    if (submitForm) {
        submitForm.addEventListener('click', checkIP)
    }
})


