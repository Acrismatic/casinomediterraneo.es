jQuery(function($) {
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

	function inheritSimpleVisibilityForCustomTypes() {
		$('.show_if_simple').each(function() {
			var $el = $(this);

			if (!$el.hasClass('show_if_torneo-poker')) {
				$el.addClass('show_if_torneo-poker');
			}
		});
	}

	function toggleTorneoTypeTabs() {
		var selectedType = $('#product-type').val();

		$('.torneo_info_options').hide();
		$('#torneo_info_product_data').hide();

		if (selectedType === 'torneo-poker') {
			$('.torneo_info_options').show();
			$('#torneo_info_product_data').show();
		}
	}

	$(document.body).on('woocommerce-product-type-change', toggleTorneoTypeTabs);
	$('#product-type').on('change', toggleTorneoTypeTabs);

	setTimeout(function() {
		inheritSimpleVisibilityForCustomTypes();
		initTorneoSelect();
		toggleTorneoTypeTabs();
		$(document.body).trigger('woocommerce-product-type-change');
	}, 0);

	$(document.body).on('woocommerce-product-type-change', initTorneoSelect);
});
