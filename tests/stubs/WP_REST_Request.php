<?php
/**
 * WP_REST_Request stub for testing
 */
declare( strict_types = 1 );

/**
 * Minimal WP_REST_Request implementation for testing
 */
class WP_REST_Request {
	private array $params = [];

	public function __construct( array $params = [] ) {
		$this->params = $params;
	}

	public function get_params(): array {
		return $this->params;
	}

	public function set_param( string $key, $value ): void {
		$this->params[ $key ] = $value;
	}

	public function get_param( string $key ) {
		return $this->params[ $key ] ?? null;
	}
}
