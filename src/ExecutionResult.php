<?php
/**
 * Recipe execution result
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

class ExecutionResult {

	public function __construct(
		private readonly bool $success,
		private readonly string $message,
		private readonly array $data = []
	) {
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
