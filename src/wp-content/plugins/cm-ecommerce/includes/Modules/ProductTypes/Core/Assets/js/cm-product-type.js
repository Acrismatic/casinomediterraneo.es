jQuery(function($) {
var CUSTOM_SIMPLE_TYPES = ['torneo-poker', 'evento'];

function initTorneoSelect() {
var $select = $('#_cm_torneo_id');

if (!$select.length || !$select.selectWoo || !window.cmTorneoSearch) {
return;
}

if ($select.data('cmTorneoReady')) {
return;
}

$select.selectWoo({
placeholder: $select.data('placeholder') || 'Selecciona un torneo',
allowClear: true,
minimumInputLength: 0,
ajax: {
url: cmTorneoSearch.ajaxUrl,
dataType: 'json',
delay: 250,
data: function(params) {
return {
action: cmTorneoSearch.action,
security: cmTorneoSearch.nonce,
term: params.term || '',
page: params.page || 1
};
},
processResults: function(response, params) {
params.page = params.page || 1;

if (!response || !response.success || !response.data) {
return { results: [] };
}

return {
results: response.data.results || [],
pagination: {
more: !!(response.data.pagination && response.data.pagination.more)
}
};
},
cache: true
}
});

$select.data('cmTorneoReady', true);
}

function inheritSimpleVisibility() {
$.each(CUSTOM_SIMPLE_TYPES, function(_, type) {
var cls = 'show_if_' + type;
$('.show_if_simple').addClass(cls);
$('.general_options').addClass(cls);
});
}

function fixCustomTypeVisibility() {
var currentType = $('select#product-type').val();
if ($.inArray(currentType, CUSTOM_SIMPLE_TYPES) === -1) {
return;
}

$('.show_if_' + currentType).show();
$('input#_manage_stock').trigger('change');

if (!$('ul.wc-tabs li.active:visible').length) {
$('ul.wc-tabs li:visible').first().find('a').trigger('click');
}
}

$(document.body).on('woocommerce-product-type-change', inheritSimpleVisibility);
$(document.body).on('woocommerce-product-type-change', initTorneoSelect);

inheritSimpleVisibility();
$('select#product-type').trigger('change');
fixCustomTypeVisibility();
initTorneoSelect();
});
