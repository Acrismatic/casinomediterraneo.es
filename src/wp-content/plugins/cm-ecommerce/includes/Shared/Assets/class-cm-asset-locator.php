<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Asset_Locator {

	/**
	 * @return array{path:string,url:string,source:string,exists:bool}
	 */
	public static function resolve( $relative_path, $module, $feature ) {
		$relative_path = ltrim( (string) $relative_path, '/' );

		if ( '' === $relative_path || false !== strpos( $relative_path, '..' ) ) {
			return array(
				'path'   => '',
				'url'    => '',
				'source' => 'invalid',
				'exists' => false,
			);
		}

		$module = (string) $module;
		$feature = (string) $feature;

		$new_relative = 'includes/Modules/' . $module . '/' . $feature . '/Assets/' . $relative_path;
		$new_path     = CM_WC_EXT_PATH . $new_relative;

		if ( file_exists( $new_path ) ) {
			return array(
				'path'   => $new_path,
				'url'    => CM_WC_EXT_URL . $new_relative,
				'source' => 'new',
				'exists' => true,
			);
		}

		/**
		 * Filters whether legacy assets fallback is allowed.
		 *
		 * @param bool   $enabled       Whether fallback is enabled.
		 * @param string $relative_path Requested relative path (inside Assets dir).
		 * @param string $module        Module name.
		 * @param string $feature       Feature name inside module.
		 */
		$legacy_enabled = (bool) apply_filters( 'cm_ecommerce_asset_legacy_fallback_enabled', true, $relative_path, $module, $feature );
		$legacy_relative = 'assets/' . $relative_path;
		$legacy_path     = CM_WC_EXT_PATH . $legacy_relative;

		if ( $legacy_enabled && file_exists( $legacy_path ) ) {
			/**
			 * Fires when a legacy path is used as fallback.
			 *
			 * @param string              $reason   Legacy usage reason.
			 * @param array<string,mixed> $context  Context payload.
			 */
			do_action(
				'cm_ecommerce_legacy_path_used',
				'asset-fallback',
				array(
					'relative_path' => $relative_path,
					'module'        => $module,
					'feature'       => $feature,
					'legacy_path'   => $legacy_path,
				)
			);

			return array(
				'path'   => $legacy_path,
				'url'    => CM_WC_EXT_URL . $legacy_relative,
				'source' => 'legacy',
				'exists' => true,
			);
		}

		return array(
			'path'   => $new_path,
			'url'    => CM_WC_EXT_URL . $new_relative,
			'source' => 'missing',
			'exists' => false,
		);
	}
}
