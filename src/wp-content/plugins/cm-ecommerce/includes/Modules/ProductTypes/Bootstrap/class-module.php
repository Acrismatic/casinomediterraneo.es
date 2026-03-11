<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Modules_ProductTypes_Module {

	public static function get_id() {
		return 'product-types';
	}

	public static function init() {
		require_once CM_WC_EXT_PATH . 'includes/Modules/ProductTypes/TorneoPoker/class-wc-product-torneo-poker.php';
		require_once CM_WC_EXT_PATH . 'includes/Modules/ProductTypes/Evento/class-wc-product-evento.php';
		require_once CM_WC_EXT_PATH . 'includes/Modules/ProductTypes/Core/class-cm-product-types.php';
		require_once CM_WC_EXT_PATH . 'includes/Modules/ProductTypes/TorneoPoker/class-cm-product-torneo-query.php';

		CM_Product_Types::init();
		CM_Product_Torneo_Query::init();

		if ( ! is_admin() ) {
			return;
		}

		require_once CM_WC_EXT_PATH . 'includes/Modules/ProductTypes/Core/class-cm-product-type-admin.php';
		require_once CM_WC_EXT_PATH . 'includes/Modules/ProductTypes/TorneoPoker/class-cm-torneo-fields.php';
		require_once CM_WC_EXT_PATH . 'includes/Modules/ProductTypes/TorneoPoker/class-cm-torneo-ajax.php';
		require_once CM_WC_EXT_PATH . 'includes/Modules/ProductTypes/TorneoPoker/class-cm-torneo-save.php';

		CM_Product_Type_Admin::init();
		CM_Torneo_Fields::init();
		CM_Torneo_Ajax::init();
		CM_Torneo_Save::init();
	}
}
