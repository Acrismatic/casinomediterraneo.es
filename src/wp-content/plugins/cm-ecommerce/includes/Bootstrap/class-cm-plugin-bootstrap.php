<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Plugin_Bootstrap {

	public static function init() {
		static $bootstrapped = false;

		if ( $bootstrapped ) {
			return;
		}

		self::load_shared_infrastructure();
		self::load_registry();

		CM_Modules_Registry::boot_modules();

		$bootstrapped = true;
	}

	private static function load_shared_infrastructure() {
		require_once CM_WC_EXT_PATH . 'includes/Shared/Assets/class-cm-asset-locator.php';
		require_once CM_WC_EXT_PATH . 'includes/Shared/Assets/class-cm-asset-registry.php';
		require_once CM_WC_EXT_PATH . 'includes/Shared/Assets/class-cm-asset-enqueuer.php';
	}

	private static function load_registry() {
		require_once CM_WC_EXT_PATH . 'includes/Bootstrap/class-cm-modules-registry.php';
	}
}
