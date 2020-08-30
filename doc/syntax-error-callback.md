# Syntax Error Callback

1. Set a path to a file with custom error callback to `--syntax-error-callback` option.
1. Create a class implementing `JakubOnderka\PhpParallelLint\Contracts\SyntaxErrorCallback` interface. File with the class must have the same name as the class inside.
1. Modify error before it is printed to the output.

## Configuration

File `MyCustomErrorHandler.php` will be passed as an argument like `./parallel-lint --syntax-error-callback ./path/to/MyCustomErrorHandler.php .`.
The content should look like:

```php

use JakubOnderka\PhpParallelLint\Contracts\SyntaxErrorCallback;
use JakubOnderka\PhpParallelLint\SyntaxError;

class MyCustomErrorHandler implements SyntaxErrorCallback {
	/**
     * @param SyntaxError $error
     * @return SyntaxError
     */
    public function errorFound(SyntaxError $error){
    	// Return new SyntaxError with custom modification to FilePath or Message
    	// Or return completely new SyntaxError extending the original one...
    	return new SyntaxError(
    		$error->getFilePath(),
    		$error->getMessage()
    	);
    }
}
```
