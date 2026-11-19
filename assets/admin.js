(function () {
    'use strict';
    function initialize() {
        var root = document.getElementById('agneovo-city-select-admin');
        var params = window.agneovoCitySelectAdmin;
        if (!root || !params || root.dataset.agneovoInitialized) return;
        var state = root.querySelector('#agneovo-city-test-state');
        var city = root.querySelector('#agneovo-city-test-city');
        if (!state || !city) return;
        root.dataset.agneovoInitialized = 'true';
        var postcode = root.querySelector('#agneovo-city-test-postcode');
        var sap = root.querySelector('#agneovo-city-test-sap');
        var message = root.querySelector('#agneovo-city-test-message');
        function emptyResult() {
            postcode.textContent = params.texts.empty;
            sap.textContent = params.texts.empty;
            message.textContent = params.texts.ready;
        }
        function updateState() {
            var selected = params.states[state.value];
            city.replaceChildren(new Option(params.texts.city, ''));
            city.disabled = !selected;
            if (selected) selected.cities.forEach(function (row) { city.add(new Option(row.label, row.value)); });
            emptyResult();
        }
        Object.keys(params.states).forEach(function (key) { state.add(new Option(params.states[key].label, key)); });
        state.addEventListener('change', updateState);
        city.addEventListener('change', function () {
            var region = params.states[state.value];
            var row = region && region.cities.find(function (item) { return item.value === city.value; });
            if (!row) { emptyResult(); return; }
            postcode.textContent = row.postcode;
            sap.textContent = region.sap + ' ' + row.sap;
            message.textContent = params.texts.complete;
        });
        root.querySelector('#agneovo-city-test-reset').addEventListener('click', function () {
            state.value = '';
            updateState();
            state.focus();
        });
        updateState();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
}());
