<?php

class Test_Shortcode_Productos_Query extends WP_UnitTestCase {

	public function test_normalize_initial_limit_defaults_to_three() {
		$this->assertSame( 3, CM_Shortcode_Productos_Query::normalize_initial_limit( 'abc' ) );
		$this->assertSame( 3, CM_Shortcode_Productos_Query::normalize_initial_limit( 0 ) );
	}

	public function test_normalize_initial_limit_respects_numeric_value() {
		$this->assertSame( 8, CM_Shortcode_Productos_Query::normalize_initial_limit( '8' ) );
	}

	public function test_normalize_ajax_payload_rejects_invalid_cursor() {
		$result = CM_Shortcode_Productos_Query::normalize_ajax_payload(
			array(
				'cursor_date_gmt' => 'invalid-date',
				'cursor_id'       => 'abc',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_payload', $result->get_error_code() );
	}
}
