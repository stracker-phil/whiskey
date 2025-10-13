<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use WP_Post;
use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\Registry\IngredientRegistry;

/**
 * Creates or updates standard WooCommerce pages.
 * Group: WordPress core / WooCommerce
 *
 * @todo untested
 */
class CreateShopPagesIngredient extends Ingredient {
	public const NAME        = 'create_shop_pages';
	public const CATEGORY    = 'woocommerce';
	public const DESCRIPTION = 'Creates or updates WooCommerce pages: shop, block-cart, block-checkout, classic-cart, classic-checkout, my-account';

	public function validate( $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		$supported_pages = $this->get_page_definitions();

		foreach ( $value as $slug ) {
			if ( ! is_string( $slug ) || ! isset( $supported_pages[ $slug ] ) ) {
				return false;
			}
		}

		return true;
	}

	public function execute( $value ): ExecutionResult {
		$page_definitions = $this->get_page_definitions();
		$created          = array();
		$updated          = array();
		$failed           = array();

		foreach ( $value as $slug ) {
			$definition = $page_definitions[ $slug ];
			$result     = $this->create_or_update_page( $slug, $definition );

			if ( $result['created'] ) {
				$created[] = $slug;
			} elseif ( $result['updated'] ) {
				$updated[] = $slug;
			} else {
				$failed[] = $slug;
			}
		}

		if ( count( $failed ) > 0 ) {
			return new ExecutionResult(
				false,
				'Failed to create or update some pages.',
				array(
					'created' => $created,
					'updated' => $updated,
					'failed'  => $failed,
				)
			);
		}

		return new ExecutionResult(
			true,
			'Shop pages created or updated successfully.',
			array(
				'created' => $created,
				'updated' => $updated,
			)
		);
	}

	private function create_or_update_page( string $slug, array $definition ): array {
		$existing_page = get_page_by_path( $slug );

		$post_data = array(
			'post_title'   => $definition['title'],
			'post_content' => $definition['content'],
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_type'    => 'page',
		);

		if ( $existing_page instanceof WP_Post ) {
			// Update existing page
			$post_data['ID'] = $existing_page->ID;
			$result          = wp_insert_post( $post_data, true );

			if ( is_wp_error( $result ) ) {
				return array(
					'created' => false,
					'updated' => false,
				);
			}

			return array(
				'created' => false,
				'updated' => true,
			);
		}

		// Create new page
		$result = wp_insert_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return array(
				'created' => false,
				'updated' => false,
			);
		}

		return array(
			'created' => true,
			'updated' => false,
		);
	}

	private function get_page_definitions(): array {
		return array(
			'shop'             => array(
				'title'   => 'Shop',
				'content' => '',
			),
			'block-cart'       => array(
				'title'   => 'Block Cart',
				'content' => '<!-- wp:woocommerce/cart -->
<div class="wp-block-woocommerce-cart alignwide is-loading"><!-- wp:woocommerce/filled-cart-block -->
<div class="wp-block-woocommerce-filled-cart-block"><!-- wp:woocommerce/cart-items-block -->
<div class="wp-block-woocommerce-cart-items-block"><!-- wp:woocommerce/cart-line-items-block -->
<div class="wp-block-woocommerce-cart-line-items-block"></div>
<!-- /wp:woocommerce/cart-line-items-block -->

<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"perPage":3,"pages":1,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/cross-sells","hideControls":["filterable"],"queryContextIncludes":["collection"],"__privatePreviewState":{"isPreview":true,"previewMessage":"Actual products will vary depending on the page being viewed."}} -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:heading {"textAlign":"left","style":{"spacing":{"margin":{"bottom":"1rem"}}}} -->
<h2 class="wp-block-heading has-text-align-left" style="margin-bottom:1rem">You may be interested in&hellip;</h2>

<!-- /wp:heading -->

<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} -->
<!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"textAlign":"center","isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}},"typography":{"lineHeight":"1.4"}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small"} /-->

<!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"fontSize":"small"} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>

<!-- /wp:woocommerce/cart-items-block -->

<!-- wp:woocommerce/cart-totals-block -->
<div class="wp-block-woocommerce-cart-totals-block"><!-- wp:woocommerce/cart-order-summary-block -->
<div class="wp-block-woocommerce-cart-order-summary-block"><!-- wp:woocommerce/cart-order-summary-heading-block -->
<div class="wp-block-woocommerce-cart-order-summary-heading-block"></div>
<!-- /wp:woocommerce/cart-order-summary-heading-block -->

<!-- wp:woocommerce/cart-order-summary-coupon-form-block -->
<div class="wp-block-woocommerce-cart-order-summary-coupon-form-block"></div>
<!-- /wp:woocommerce/cart-order-summary-coupon-form-block -->

<!-- wp:woocommerce/cart-order-summary-subtotal-block -->
<div class="wp-block-woocommerce-cart-order-summary-subtotal-block"></div>
<!-- /wp:woocommerce/cart-order-summary-subtotal-block -->

<!-- wp:woocommerce/cart-order-summary-fee-block -->
<div class="wp-block-woocommerce-cart-order-summary-fee-block"></div>
<!-- /wp:woocommerce/cart-order-summary-fee-block -->

<!-- wp:woocommerce/cart-order-summary-discount-block -->
<div class="wp-block-woocommerce-cart-order-summary-discount-block"></div>
<!-- /wp:woocommerce/cart-order-summary-discount-block -->

<!-- wp:woocommerce/cart-order-summary-shipping-block -->
<div class="wp-block-woocommerce-cart-order-summary-shipping-block"></div>
<!-- /wp:woocommerce/cart-order-summary-shipping-block -->

<!-- wp:woocommerce/cart-order-summary-taxes-block -->
<div class="wp-block-woocommerce-cart-order-summary-taxes-block"></div>
<!-- /wp:woocommerce/cart-order-summary-taxes-block --></div>
<!-- /wp:woocommerce/cart-order-summary-block -->

<!-- wp:woocommerce/cart-express-payment-block -->
<div class="wp-block-woocommerce-cart-express-payment-block"></div>
<!-- /wp:woocommerce/cart-express-payment-block -->

<!-- wp:woocommerce/proceed-to-checkout-block -->
<div class="wp-block-woocommerce-proceed-to-checkout-block"></div>
<!-- /wp:woocommerce/proceed-to-checkout-block -->

<!-- wp:woocommerce/cart-accepted-payment-methods-block -->
<div class="wp-block-woocommerce-cart-accepted-payment-methods-block"></div>
<!-- /wp:woocommerce/cart-accepted-payment-methods-block --></div>
<!-- /wp:woocommerce/cart-totals-block --></div>
<!-- /wp:woocommerce/filled-cart-block -->

<!-- wp:woocommerce/empty-cart-block -->
<div class="wp-block-woocommerce-empty-cart-block"><!-- wp:heading {"textAlign":"center","className":"with-empty-cart-icon wc-block-cart__empty-cart__title"} -->
<h2 class="wp-block-heading has-text-align-center with-empty-cart-icon wc-block-cart__empty-cart__title">Your cart is currently empty!</h2>
<!-- /wp:heading -->

<!-- wp:separator {"className":"is-style-dots"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>
<!-- /wp:separator -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">New in store</h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-new {"columns":4,"rows":1} /--></div>
<!-- /wp:woocommerce/empty-cart-block --></div>
<!-- /wp:woocommerce/cart -->',
			),
			'block-checkout'   => array(
				'title'   => 'Block Checkout',
				'content' => '<!-- wp:woocommerce/checkout -->
<div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading"><!-- wp:woocommerce/checkout-fields-block -->
<div class="wp-block-woocommerce-checkout-fields-block"><!-- wp:woocommerce/checkout-express-payment-block -->
<div class="wp-block-woocommerce-checkout-express-payment-block"></div>
<!-- /wp:woocommerce/checkout-express-payment-block -->

<!-- wp:woocommerce/checkout-contact-information-block -->
<div class="wp-block-woocommerce-checkout-contact-information-block"></div>
<!-- /wp:woocommerce/checkout-contact-information-block -->

<!-- wp:woocommerce/checkout-shipping-method-block -->
<div class="wp-block-woocommerce-checkout-shipping-method-block"></div>
<!-- /wp:woocommerce/checkout-shipping-method-block -->

<!-- wp:woocommerce/checkout-pickup-options-block -->
<div class="wp-block-woocommerce-checkout-pickup-options-block"></div>
<!-- /wp:woocommerce/checkout-pickup-options-block -->

<!-- wp:woocommerce/checkout-shipping-address-block -->
<div class="wp-block-woocommerce-checkout-shipping-address-block"></div>
<!-- /wp:woocommerce/checkout-shipping-address-block -->

<!-- wp:woocommerce/checkout-billing-address-block -->
<div class="wp-block-woocommerce-checkout-billing-address-block"></div>
<!-- /wp:woocommerce/checkout-billing-address-block -->

<!-- wp:woocommerce/checkout-shipping-methods-block -->
<div class="wp-block-woocommerce-checkout-shipping-methods-block"></div>
<!-- /wp:woocommerce/checkout-shipping-methods-block -->

<!-- wp:woocommerce/checkout-payment-block -->
<div class="wp-block-woocommerce-checkout-payment-block"></div>
<!-- /wp:woocommerce/checkout-payment-block -->

<!-- wp:woocommerce/checkout-additional-information-block -->
<div class="wp-block-woocommerce-checkout-additional-information-block"></div>
<!-- /wp:woocommerce/checkout-additional-information-block -->

<!-- wp:woocommerce/checkout-order-note-block -->
<div class="wp-block-woocommerce-checkout-order-note-block"></div>
<!-- /wp:woocommerce/checkout-order-note-block -->

<!-- wp:woocommerce/checkout-terms-block -->
<div class="wp-block-woocommerce-checkout-terms-block"></div>
<!-- /wp:woocommerce/checkout-terms-block -->

<!-- wp:woocommerce/checkout-actions-block -->
<div class="wp-block-woocommerce-checkout-actions-block"></div>
<!-- /wp:woocommerce/checkout-actions-block --></div>
<!-- /wp:woocommerce/checkout-fields-block -->

<!-- wp:woocommerce/checkout-totals-block -->
<div class="wp-block-woocommerce-checkout-totals-block"><!-- wp:woocommerce/checkout-order-summary-block -->
<div class="wp-block-woocommerce-checkout-order-summary-block"><!-- wp:woocommerce/checkout-order-summary-cart-items-block -->
<div class="wp-block-woocommerce-checkout-order-summary-cart-items-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-cart-items-block -->

<!-- wp:woocommerce/checkout-order-summary-coupon-form-block -->
<div class="wp-block-woocommerce-checkout-order-summary-coupon-form-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-coupon-form-block -->

<!-- wp:woocommerce/checkout-order-summary-subtotal-block -->
<div class="wp-block-woocommerce-checkout-order-summary-subtotal-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-subtotal-block -->

<!-- wp:woocommerce/checkout-order-summary-fee-block -->
<div class="wp-block-woocommerce-checkout-order-summary-fee-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-fee-block -->

<!-- wp:woocommerce/checkout-order-summary-discount-block -->
<div class="wp-block-woocommerce-checkout-order-summary-discount-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-discount-block -->

<!-- wp:woocommerce/checkout-order-summary-shipping-block -->
<div class="wp-block-woocommerce-checkout-order-summary-shipping-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-shipping-block -->

<!-- wp:woocommerce/checkout-order-summary-taxes-block -->
<div class="wp-block-woocommerce-checkout-order-summary-taxes-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-taxes-block --></div>
<!-- /wp:woocommerce/checkout-order-summary-block --></div>
<!-- /wp:woocommerce/checkout-totals-block --></div>
<!-- /wp:woocommerce/checkout -->',
			),
			'my-account'       => array(
				'title'   => 'My account',
				'content' => '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->',
			),
			'classic-cart'     => array(
				'title'   => 'Classic Cart',
				'content' => '<!-- wp:woocommerce/classic-shortcode /-->',
			),
			'classic-checkout' => array(
				'title'   => 'Classic Checkout',
				'content' => '<!-- wp:woocommerce/classic-shortcode {"shortcode":"checkout"} /-->',
			),
		);
	}
}

add_action(
	'whiskey:register_ingredient',
	static fn( IngredientRegistry $registry ) => $registry->add( CreateShopPagesIngredient::class )
);
