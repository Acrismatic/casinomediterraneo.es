<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_Evento extends WC_Product_Simple {

	public function get_type() {
		return 'evento';
	}
}
