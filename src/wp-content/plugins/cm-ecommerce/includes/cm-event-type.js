jQuery(function($){
	var $select = $('#_cm_evento_id');

	if (!$select.length || !$select.selectWoo || !window.cmEventoSearch) {
		return;
	}

	$select.selectWoo({
		placeholder: $select.data('placeholder') || 'Selecciona un evento',
		allowClear: true,
		minimumInputLength: 0,
		ajax: {
			url: cmEventoSearch.ajaxUrl,
			dataType: 'json',
			delay: 250,
			data: function(params) {
				return {
					action: cmEventoSearch.action,
					security: cmEventoSearch.nonce,
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
});
