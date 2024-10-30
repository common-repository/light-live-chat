# Data Types
[![Latest Stable Version](https://poser.pugx.org/lightsource/data-types/v)](//packagist.org/packages/lightsource/data-types)
[![Total Downloads](https://poser.pugx.org/lightsource/data-types/downloads)](//packagist.org/packages/lightsource/data-types)
[![Monthly Downloads](https://poser.pugx.org/lightsource/data-types/d/monthly)](//packagist.org/packages/lightsource/data-types)
[![Daily Downloads](https://poser.pugx.org/lightsource/data-types/d/daily)](//packagist.org/packages/lightsource/data-types)
[![License](https://poser.pugx.org/lightsource/data-types/license)](//packagist.org/packages/lightsource/data-types)

## What is it
Class for complex data cleaning depending on a type

## Installation
```
composer require lightsource/data-types
```

## Example of usage

```
use LightSource\DataTypes\DATA_TYPES;
use LightSource\StdResponse\STD_RESPONSE;

require_once __DIR__ . '/vendor/autoload.php';

$result = DATA_TYPES::Clear( DATA_TYPES::INT, '10', [
	DATA_TYPES::_MIN => 1,
	DATA_TYPES::_MAX => 20,
] );

if ( $result[ STD_RESPONSE::IS_SUCCESS ] ) {
	$value = $result[ STD_RESPONSE::ARGS ][ DATA_TYPES::_ARG__VALUE ];
	// TODO
} else {
	$errorMsgs = $result[ STD_RESPONSE::E_MSGS ];
	// TODO
}
```
