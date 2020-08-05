 ## Config example

 ```php
 add_filter( 'jet-engine-extend-form-actions/config', function() {
	return array(
		123 => array(
			'_form_field_1' => array(
				'prop' => 'post_meta',
				'key'  => '_meta_key',
			),
			'_form_field_2' => array(
				'prop' => 'post_terms',
				'tax'  => 'taxonomy_slug',
				'by'   => 'name',
			),
			'_form_field_3' => array(
				'prop'   => 'post_data',
				'key'    => 'post_title',
				'suffix' => ' -',
			),
			'_form_field_4' => array(
				'prop'   => 'post_data',
				'key'    => 'post_title',
				'prefix' => ' ',
			),
		),
	);
 } );
  ```

 Where:

 - *123* - required form ID (you can find it in the address bar on the edit for screen).
 - *_form_field_1, _form_field_2, ...* - names of the submitted form field to get data from.
 - *prop* - 'post_data', 'post_meta' or 'post_terms' - type of data to set.
 - *key* - for 'post_meta' prop - is the meta key name to set, for 'post_data' - is the property of the post object to set.
 - *tax* - for 'post_terms' prop - taxonomy name to insert new terms into. You can also set 'tax' argument for *post_meta* in cases when you need to duplicate selected term from current taxonomy into meta field.
 - *prefix, suffix* - this arguments are used when you combining multiple fiels values into the same post field or meta key. Prefix is what need to be added before combined field, suffix - after
 - *by* - for the terms input is way how terms will be processed - if value is set to 'id' - passed terms IDs will be attaqched to post, with any other values - plugin will create term at first and than attach it to post

## How to use
- Download, install and activate plugin;
- Add configuration code (see example above) into the end of **functions.php** file of your active theme.

## Combine post title from multiple form fields example

#### Meta fields only:

```php
add_filter( 'jet-engine-extend-form-actions/config', function() {
	return array(
		537 => array(
			'_form_field_1' => array(
				'prop'   => 'post_data',
				'key'    => 'post_title',
				'prefix' => '#',
				'suffix' => ':',
			),
			'_form_field_2' => array(
				'prop'   => 'post_data',
				'key'    => 'post_title',
				'prefix' => ' ',
				'suffix' => '!',
			),
		),
	);
} );
```

#### Meta fields combined with taxonomy terms:

```php
add_filter( 'jet-engine-extend-form-actions/config', function() {
	return array(
		537 => array(
			'_test_field_2' => array(
				'prop' => 'post_data',
				'key'  => 'post_title',
			),
			'_test_field' => array(
				'prop'   => 'post_data',
				'key'    => 'post_title',
				'tax'    => 'category',
				'prefix' => ', ',
			),
		),
	);
} );
```
## Multiple props for the same form field example

```php
add_filter( 'jet-engine-extend-form-actions/config', function() {
	return array(
		39 => array(
			'inserted_post_id' => array(
				'props' => array(
					array(
						'prop'   => 'post_data',
						'key'    => 'post_title',
						'prefix' => '#',
					),
					array(
						'prop'   => 'post_data',
						'key'    => 'post_name',
					),
				),
			),
		),
	);
} );
```
