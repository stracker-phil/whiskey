# Missing Ingredients

## PayPal

1. PayPalLegacySettings - arg: bool (true = legacy mode, false = modern UI)
    - DB: ??
2. PayPalBrandedOnly - arg: bool (true = branded-only mode, false = whitelabel mode)
    - DB: ??
3. PayPalBcdcOverride - arg: bool/array (true = enable override flag, false = delete the flag, array = save the array to the DB)
   - DB: ??
4. PayPalMerchant - arg: array (merchant ID, API key) or false (not onboarded)
    - DB: ??
5. PayPalStartOver - arg: none
    - Delete all PayPal option items, i.e., fully reset PayPal state in the DB 


## WooCommerce

1. SetWooStorePages - arg: array (cart: slug, checkout: slug)
   - Changes the store pages, e.g. to switch default store to block checkout or classic checkout

## General

1. RecipeIngredient - arg: string (name of another recipe, which will be executed)
   - Allows stacking of recipes. Needs some sort of tracking to prevent infinite loops
