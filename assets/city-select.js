(function ($) {
    'use strict';
    $(function () {
        if (!window.agneovoCitySelectParams || window.agneovoCitySelectInitialized) return;
        window.agneovoCitySelectInitialized = true;
        var params = window.agneovoCitySelectParams;
        var citySelector = '#billing_city, #shipping_city, #calc_shipping_city';
        var contextSelector = '#billing_country, #shipping_country, #calc_shipping_country, #billing_state, #shipping_state, #calc_shipping_state';
        var contexts = new WeakMap();
        var queued = false;
        function normalized(value) { return String(value || '').trim().replace(/\s+/g, ' ').replace(/臺/g, '台').toUpperCase(); }
        function stateKey(value) {
            var needle = normalized(value);
            return Object.keys(params.states).find(function (key) { return params.states[key].aliases.some(function (alias) { return normalized(alias) === needle; }); }) || '';
        }
        function prefixFor(element) { return String(element.id || '').replace(/_(?:country|state|city)$/, ''); }
        function group($city) {
            var prefix = prefixFor($city[0]);
            var root = $city.closest('form')[0] || $city.closest('.form-row').parent()[0] || document.body;
            var $root = $(root);
            var all = contexts.get(root) || {};
            contexts.set(root, all);
            return { root: root, prefix: prefix, state: all[prefix] || (all[prefix] = {}), country: String($root.find('#' + prefix + '_country').val() || ''), region: String($root.find('#' + prefix + '_state').val() || ''), postcode: $root.find('#' + prefix + '_postcode') };
        }
        function rowFor(rows, value) {
            var needle = normalized(value);
            return rows.find(function (row) { return row.aliases.some(function (alias) { return normalized(alias) === needle; }); });
        }
        function enhance($city) {
            if (!$city.is('select') || !$.fn.selectWoo || $city.data('select2')) return;
            var texts = window.wc_country_select_params || {};
            $city.selectWoo({ width: '100%', placeholder: $city.attr('data-placeholder') || $city.attr('placeholder') || params.placeholder, language: { noResults: function () { return texts.i18n_no_matches || ''; }, searching: function () { return texts.i18n_searching || ''; } } });
        }
        function replaceControl($old, select) {
            if ($old.is(select ? 'select' : 'input')) return $old;
            if ($old.data('select2') && $.fn.selectWoo) $old.selectWoo('destroy');
            var $next = $(select ? '<select>' : '<input type="text">');
            Array.from($old[0].attributes).forEach(function (attribute) {
                if (!['type', 'class', 'data-select2-id', 'aria-hidden', 'tabindex'].includes(attribute.name)) $next.attr(attribute.name, attribute.value);
            });
            var classes = String($old.attr('class') || '').split(/\s+/).filter(function (name) { return name && !['select2-hidden-accessible', 'city_select', 'agneovo-city-select'].includes(name); });
            $next.attr('class', classes.join(' ')).addClass(select ? 'city_select agneovo-city-select' : 'input-text');
            $next.val($old.val());
            $old.replaceWith($next);
            return $next;
        }
        function setPostcode(groupInfo, value) {
            if (!groupInfo.postcode.length || groupInfo.postcode.prop('disabled') || groupInfo.postcode.is('[readonly]')) return;
            groupInfo.postcode.val(value).trigger('change');
        }
        function refresh($city) {
            if (!$city.length || $city.prop('disabled') || $city.is('[readonly]')) return;
            var info = group($city);
            var key = stateKey(info.region);
            var context = info.country + '|' + info.region;
            var changed = info.state.context !== undefined && info.state.context !== context;
            var value = String($city.val() || '');
            if (changed) { value = ''; setPostcode(info, ''); }
            if (info.country !== 'TW' || (info.region && !key)) {
                $city = replaceControl($city, false);
                $city.val(value);
                info.state.context = context; info.state.value = value; info.state.rows = [];
                return;
            }
            var rows = key ? params.states[key].cities : [];
            var signature = key + '|' + JSON.stringify(rows);
            var needsOptions = !$city.is('select') || $city.data('agneovoCitySignature') !== signature || changed;
            $city = replaceControl($city, true);
            if (needsOptions) {
                if ($city.data('select2') && $.fn.selectWoo) $city.selectWoo('destroy');
                $city.empty().append(new Option($('<span>').html(params.placeholder).text(), ''));
                var selected = rowFor(rows, value);
                rows.forEach(function (row) { $city.append(new Option(row.name, selected === row && value ? value : row.name)); });
                if (value && !selected) $city.append(new Option(value, value));
                $city.val(value);
                $city.data('agneovoCitySignature', signature);
            }
            info.state.context = context; info.state.value = value; info.state.rows = rows;
            // Empty postcodes may be filled; existing manual values are never replaced on hydration.
            var row = rowFor(rows, value);
            if (row && !String(info.postcode.val() || '')) setPostcode(info, row.postcode);
            enhance($city);
            $city.trigger('change.select2');
        }
        function refreshAll() { $(citySelector).each(function () { refresh($(this)); }); }
        function schedule() {
            if (queued) return;
            queued = true;
            (window.requestAnimationFrame || window.setTimeout)(function () { queued = false; refreshAll(); });
        }
        $(document.body).on('change.agneovoCity', contextSelector, function () {
            var prefix = prefixFor(this);
            $(citySelector).filter(function () { return prefixFor(this) === prefix; }).each(function () { refresh($(this)); });
        });
        $(document.body).on('country_to_state_changed.agneovoCity country_to_state_changing.agneovoCity updated_checkout.agneovoCity updated_cart_totals.agneovoCity updated_wc_div.agneovoCity', schedule);
        $(document.body).on('change.agneovoCity select2:select.agneovoCity select2:clear.agneovoCity', citySelector, function () {
            var $city = $(this);
            if (!$city.is('select') || $city.prop('disabled') || $city.is('[readonly]')) return;
            var info = group($city); var value = String($city.val() || '');
            if (value === info.state.value) return;
            info.state.value = value;
            var row = rowFor(info.state.rows || [], value);
            setPostcode(info, row ? row.postcode : '');
        });
        refreshAll();
        if (window.MutationObserver) {
            var observer = new MutationObserver(function (changes) {
                var changed = changes.some(function (change) { return Array.from(change.addedNodes).some(function (node) { return node.nodeType === 1 && (node.matches(citySelector) || node.querySelector(citySelector)); }); });
                if (changed) schedule();
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    });
}(jQuery));
