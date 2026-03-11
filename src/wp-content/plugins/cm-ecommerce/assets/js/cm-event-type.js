jQuery(function($) {
	function initEventoSelect() {
		var $select = $('#_cm_evento_id');

		if (!$select.length || !$select.selectWoo || !window.cmEventoSearch) {
			return;
		}

		if ($select.data('cmEventoReady')) {
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

		$select.data('cmEventoReady', true);
	}

	function inheritSimpleVisibilityForEventoType() {
		$('.show_if_simple').each(function() {
			var $el = $(this);

			if (!$el.hasClass('show_if_evento')) {
				$el.addClass('show_if_evento');
			}
		});
	}

	function toggleEventoTypeTabs() {
		var selectedType = $('#product-type').val();

		$('.evento_info_options').hide();
		$('#evento_info_product_data').hide();

		if (selectedType === 'evento') {
			$('.evento_info_options').show();
			$('#evento_info_product_data').show();
		}
	}

	$(document.body).on('woocommerce-product-type-change', toggleEventoTypeTabs);
	$('#product-type').on('change', toggleEventoTypeTabs);

	setTimeout(function() {
		inheritSimpleVisibilityForEventoType();
		initEventoSelect();
		toggleEventoTypeTabs();
		$(document.body).trigger('woocommerce-product-type-change');
	}, 0);

	$(document.body).on('woocommerce-product-type-change', initEventoSelect);
});
