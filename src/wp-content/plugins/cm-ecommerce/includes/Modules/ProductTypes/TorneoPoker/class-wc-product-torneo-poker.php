<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_Torneo_Poker extends WC_Product_Simple {

	public function get_type() {
		return 'torneo-poker';
	}
}
