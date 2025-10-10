<?php
/**
 * Recipe Execution Result
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

/**
 * Result of recipe execution
 */
class ExecutionResult {

	private bool $success;
	private string $message;
	private array $data;

	public function __construct( bool $success, string $message, array $data = [] ) {
		$this->success = $success;
		$this->message = $message;
		$this->data    = $data;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_message(): string {
		return $this->message;
	}

	public function get_data(): array {
		return $this->data;
	}

	/**
	 * Convert to array representation
	 *
	 * @explain Useful for REST API responses and serialization.
	 */
	public function to_array(): array {
		return [
			'success' => $this->success,
			'message' => $this->message,
			'data'    => $this->data,
		];
	}
}
