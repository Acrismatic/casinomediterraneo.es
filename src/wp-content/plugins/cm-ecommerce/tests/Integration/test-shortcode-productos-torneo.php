<?php

class Test_Shortcode_Productos_Torneo extends WP_UnitTestCase {

	public function test_productos_torneo_shortcode_is_registered() {
		global $shortcode_tags;

		CM_Shortcode_Productos::init();

		$this->assertArrayHasKey( 'productos_torneo', $shortcode_tags );
	}

	public function test_legacy_torneo_shortcode_returns_migration_message() {
		CM_Shortcode_Productos::init();

		$html = do_shortcode( '[PRODUCTOS tipo="torneo"]' );

		$this->assertStringContainsString( 'productos_torneo', $html );
	}
}
