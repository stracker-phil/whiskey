<?php
/**
 * WP_Post stub for testing
 */

declare( strict_types = 1 );

class WP_Post {
	public int $ID = 0;
	public string $post_type = '';
	public string $post_name = '';
	public string $post_title = '';
	public string $post_content = '';
	public string $post_status = '';

	public function __construct(
		int $id = 0,
		string $post_type = 'page',
		string $post_name = '',
		string $post_title = '',
		string $post_content = '',
		string $post_status = 'publish'
	) {
		$this->ID           = $id;
		$this->post_type    = $post_type;
		$this->post_name    = $post_name;
		$this->post_title   = $post_title;
		$this->post_content = $post_content;
		$this->post_status  = $post_status;
	}
}
