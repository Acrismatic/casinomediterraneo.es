<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CM_Asset_Enqueuer {

	public static function enqueue( $handle ) {
		$descriptor = CM_Asset_Registry::get( $handle );
		if ( ! is_array( $descriptor ) ) {
			return false;
		}

		$resolved = CM_Asset_Locator::resolve(
			(string) $descriptor['relative_path'],
			(string) $descriptor['module'],
			(string) $descriptor['feature']
		);

		if ( empty( $resolved['exists'] ) ) {
			/**
			 * Fires when an asset handle cannot be resolved to an existing file.
			 *
			 * @param string               $handle     Requested asset handle.
			 * @param array<string,mixed>  $descriptor Asset descriptor from registry.
			 * @param array<string,mixed>  $resolved   Resolution payload from locator.
			 */
			do_action(
				'cm_ecommerce_asset_resolution_error',
				$handle,
				$descriptor,
				$resolved
			);
			return false;
		}

		$deps    = isset( $descriptor['deps'] ) && is_array( $descriptor['deps'] ) ? $descriptor['deps'] : array();
		$version = self::resolve_version( $handle, $descriptor, $resolved );

		if ( 'script' === $descriptor['type'] ) {
			$in_footer = isset( $descriptor['in_footer'] ) ? (bool) $descriptor['in_footer'] : true;
			wp_enqueue_script( $handle, (string) $resolved['url'], $deps, $version, $in_footer );
			return true;
		}

		wp_enqueue_style( $handle, (string) $resolved['url'], $deps, $version );
		return true;
	}

	/**
	 * @param array<string,mixed> $descriptor
	 * @param array<string,mixed> $resolved
	 */
	private static function resolve_version( $handle, array $descriptor, array $resolved ) {
		$strategy = isset( $descriptor['version_strategy'] ) ? (string) $descriptor['version_strategy'] : 'auto';

		$strategy = (string) apply_filters(
			'cm_ecommerce_asset_version_strategy',
			$strategy,
			$handle,
			$descriptor,
			$resolved
		);

		if ( 'plugin' === $strategy ) {
			return CM_WC_EXT_VERSION;
		}

		if ( 'mtime' === $strategy ) {
			return self::filemtime_or_plugin_version( (string) $resolved['path'] );
		}

		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';

		if ( 'production' === $environment ) {
			return CM_WC_EXT_VERSION;
		}

		return self::filemtime_or_plugin_version( (string) $resolved['path'] );
	}

	private static function filemtime_or_plugin_version( $path ) {
		$mtime = file_exists( $path ) ? filemtime( $path ) : false;

		if ( false === $mtime ) {
			return CM_WC_EXT_VERSION;
		}

		return (string) $mtime;
	}
}
