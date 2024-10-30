# Std Response

## What is it
Standardization for a function / method response with multiple arguments

## Installation
```
composer require lightsource/std-response
```

## Example of usage

```
use LightSource\StdResponse\STD_RESPONSE;

require_once __DIR__ . '/vendor/autoload.php';

function calc( $a, $b ) {

	$value     = $a + $b;
	$isSuccess = $value > $a;

	return STD_RESPONSE::Create( [ 'value' => 10, ], $isSuccess );
}

$result = calc( 10, 20 );

if ( $result[ STD_RESPONSE::IS_SUCCESS ] ) {
	$value = $result[ STD_RESPONSE::ARGS ]['value'];
	// TODO
} else {
	$errorMessages = $result[ STD_RESPONSE::E_MSGS ];
	// TODO
}
```
