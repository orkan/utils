# Utils `v11.3.0`
Bunch of scripts collected from all around the HDD

# Console app
`src/Application.php`

Simple, configurable, extendable, easy to use CLI app implementation.

## Usage
```php
// Define app config and modules
$Factory = new Factory([
	'app_title'   => 'My CLI app',
]);

// Initialize PHP env, load cmd line switches, set error handlers, etc...
$App = new Application( $Factory );
$App->run();

// Do something!
$Factory->Logger()->notice( 'Hello from ' . $Factory->get( 'app_title' ) );
```
See included tools in /bin dir for more examples.

# FORM Input generator
`src/Input.php`

HTML/PHP `<form>` input parser.
Allows automatic import of values form POST like data arrays with value filtering.
Allows rendering html input elements straight from php array.

## Usage
Define:
```php
$fields = [
	'text' => [
		'type'   => 'text',
		'filter' => 'strtoupper',
	],
	'radios' => [
		'type'   => 'radio',
		'defval' => 'radC',
		'items'  => [
			'radA' => 'Tag A',
			'radB' => 'Tag B',
			'radC' => 'Tag C',
		],
	],
];
```
Parse:
```php
foreach ( $fields as $name => $field ) {
	$Input = new Input( $field, $_POST ); // Create Input with value extracted from POST array
	saveDB( $name, $Input->val() ); // Save filtered value to DB
	echo $Input->getContents(); // Render element on HTML page
}
```

# Thumbnail generator
`src/Thumbnail.php`

@todo Add description...

# PHP cli apps

- `bin/app_env_switch.php` - Switch between multiple configuration files.
- `bin/app_files_quantity.php` - Copy files from one dir to another with quantity limit and sorting features.

## About
### Third Party Packages
- none

### Installation
`$ composer require orkan/utils`

### Author
[Orkan](https://github.com/orkan)

### License
MIT

### Updated
Mon, 31 Mar 2025 06:32:10 +02:00
