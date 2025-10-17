<?php
/**
 * WP_Theme stub for testing
 */

declare( strict_types = 1 );

class WP_Theme {
	private string $stylesheet;
	private bool $exists;
	private string $name;
	private string $version;
	private array $errors;

	public function __construct(
		string $stylesheet = 'twentytwentyfour',
		bool $exists = true,
		string $name = 'Twenty Twenty-Four',
		string $version = '1.0',
		array $errors = []
	) {
		$this->stylesheet = $stylesheet;
		$this->exists     = $exists;
		$this->name       = $name;
		$this->version    = $version;
		$this->errors     = $errors;
	}

	public function exists(): bool {
		return $this->exists;
	}

	public function get_stylesheet(): string {
		return $this->stylesheet;
	}

	public function get( string $header ): string {
		switch ( $header ) {
			case 'Name': return $this->name;
			case 'Version': return $this->version;
			default: return '';
		}
	}

	public function errors(): array {
		return $this->errors;
	}
}
