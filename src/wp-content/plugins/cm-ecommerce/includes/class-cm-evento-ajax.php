<?php

if (! defined('ABSPATH')) {
	exit;
}

class CM_Evento_Ajax
{
	const NONCE_ACTION = 'cm_search_eventos';
	const POSTS_PER_PAGE = 20;

	public static function init()
	{
		add_action('wp_ajax_cm_search_eventos', array(__CLASS__, 'search_eventos'));
	}

	public static function search_eventos()
	{
		if (! check_ajax_referer(self::NONCE_ACTION, 'security', false)) {
			wp_send_json_error(array('message' => __('Nonce inválido.', 'cm-wc-extensions')), 403);
		}

		if (! current_user_can('edit_products')) {
			wp_send_json_error(array('message' => __('No autorizado.', 'cm-wc-extensions')), 403);
		}

		$term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
		$page = isset($_GET['page']) ? max(1, absint(wp_unslash($_GET['page']))) : 1;

		$query_args = array(
			'post_type'      => 'casino_evento',
			'post_status'    => 'publish',
			'posts_per_page' => self::POSTS_PER_PAGE,
			'paged'          => $page,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		);

		if ($term !== '') {
			$query_args['s'] = $term;
		}

		$query = new WP_Query($query_args);

		$results = array();
		foreach ($query->posts as $evento_id) {
			$label = get_the_title($evento_id);
			$date  = get_post_meta($evento_id, 'evento_fecha', true);

			if (! empty($date)) {
				$timestamp = strtotime((string) $date);
				if (false !== $timestamp) {
					$label .= ' - ' . wp_date('d/m/Y', $timestamp);
				}
			}

			$results[] = array(
				'id'   => $evento_id,
				'text' => $label,
			);
		}

		wp_send_json_success(array(
			'results'    => $results,
			'pagination' => array(
				'more' => $page < (int) $query->max_num_pages,
			),
		));
	}
}

CM_Evento_Ajax::init();

function cm_enqueue_admin_evento_script($hook)
{
	if ('post.php' !== $hook && 'post-new.php' !== $hook) {
		return;
	}

	wp_enqueue_script(
		'cm-admin-evento-script',
		CM_WC_EXT_URL . 'assets/js/cm-event-type.js',
		array('jquery', 'wc-enhanced-select'),
		CM_WC_EXT_VERSION,
		true
	);

	wp_localize_script(
		'cm-admin-evento-script',
		'cmEventoSearch',
		array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'action'  => 'cm_search_eventos',
			'nonce'   => wp_create_nonce(CM_Evento_Ajax::NONCE_ACTION),
		)
	);
}
add_action('admin_enqueue_scripts', 'cm_enqueue_admin_evento_script');
