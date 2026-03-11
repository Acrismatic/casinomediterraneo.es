<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Modules_Registry {

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public static function modules() {
		$modules = array(
			'ProductTypes' => array(
				'priority' => 10,
				'file'     => CM_WC_EXT_PATH . 'includes/Modules/ProductTypes/Bootstrap/class-module.php',
				'class'    => 'CM_Modules_ProductTypes_Module',
			),
			'Catalog'      => array(
				'priority' => 20,
				'file'     => CM_WC_EXT_PATH . 'includes/Modules/Catalog/Bootstrap/class-module.php',
				'class'    => 'CM_Modules_Catalog_Module',
			),
		);

		/**
		 * Filters the modules registry map.
		 *
		 * @param array<string,array<string,mixed>> $modules Modules indexed by module name.
		 */
		$modules = apply_filters( 'cm_ecommerce_modules_registry', $modules );

		uasort(
			$modules,
			static function( $left, $right ) {
				$left_priority  = isset( $left['priority'] ) ? (int) $left['priority'] : 999;
				$right_priority = isset( $right['priority'] ) ? (int) $right['priority'] : 999;

				if ( $left_priority === $right_priority ) {
					return 0;
				}

				return $left_priority <=> $right_priority;
			}
		);

		return $modules;
	}

	public static function boot_modules() {
		foreach ( self::modules() as $module_name => $module_config ) {
			$file_path = isset( $module_config['file'] ) ? (string) $module_config['file'] : '';
			$class     = isset( $module_config['class'] ) ? (string) $module_config['class'] : '';

			if ( '' === $file_path || ! file_exists( $file_path ) ) {
				self::trace_invalid_module( (string) $module_name, 'missing_file', $module_config );
				continue;
			}

			require_once $file_path;

			if ( '' === $class || ! class_exists( $class ) || ! method_exists( $class, 'init' ) ) {
				self::trace_invalid_module( (string) $module_name, 'invalid_entrypoint', $module_config );
				continue;
			}

			$class::init();
		}
	}

	/**
	 * @param array<string,mixed> $module_config
	 */
	private static function trace_invalid_module( $module_name, $reason, array $module_config ) {
		/**
		 * Fires when a module registry entry cannot be initialized.
		 *
		 * @param array{module:string,reason:string,config:array<string,mixed>} $error Module registration error payload.
		 */
		do_action(
			'cm_ecommerce_module_registration_error',
			array(
				'module' => $module_name,
				'reason' => $reason,
				'config' => $module_config,
			)
		);
	}
}
