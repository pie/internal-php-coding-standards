# Pie PHP Coding Standards

Internal PHPCS ruleset extending the [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards) with Pie-specific rules.

## Rules

Inherits all WordPress rules, plus:

| Sniff | Description |
|-------|-------------|
| `PieStandard.PHP.DiscouragedEmpty` | Discourages use of `empty()` in favour of explicit checks |
| `PieStandard.PHP.AvoidNestedDirname` | Discourages nesting `dirname()` calls to traverse directories |
| `PieStandard.PHP.AvoidLooseTruthiness` | Discourages bare `if ( $var )` and `if ( ! $var )` checks |
| `PieStandard.WordPress.EnqueueFilemtimeVersion` | Requires `filemtime()` for asset versioning in `wp_enqueue_*` calls |

## Setup

**1. Require globally**

```bash
composer global require pieweb/internal-php-coding-standards
```

**2. Set the standard in VS Code** (`.vscode/settings.json` or user settings)

```json
"phpsab.standard": "PieStandard"
```

**3. Verify the standard is registered**

```bash
phpcs -i
```

`PieStandard` should appear in the list.

---

To update to the latest version:

```bash
composer global update pieweb/internal-php-coding-standards
```

## Adding a new sniff

**1. Create the sniff class** in `PieStandard/Sniffs/{Category}/YourSniffNameSniff.php`:

```php
<?php
/**
 * Description of what this sniff checks.
 *
 * @package PieStandard
 */

namespace PieStandard\Sniffs\{Category};

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Full description of the rule and why it exists.
 */
class YourSniffNameSniff implements Sniff {

    /**
     * Returns the token types this sniff is interested in.
     *
     * @return int[]
     */
    public function register() {
        return array( T_STRING ); // Replace with relevant token(s).
    }

    /**
     * Processes the token.
     *
     * @param File $phpcs_file The file being scanned.
     * @param int  $stack_ptr  The position of the current token in the stack.
     *
     * @return void
     */
    public function process( File $phpcs_file, $stack_ptr ) {
        // Your logic here.
        $phpcs_file->addWarning( 'Your warning message.', $stack_ptr, 'ErrorCode' );
    }
}
```

Categories follow the same convention as WordPress sniffs: `PHP`, `WordPress`, `WhiteSpace`, `NamingConventions`, etc.

**2. Register the sniff in `PieStandard/ruleset.xml`:**

```xml
<rule ref="PieStandard.{Category}.YourSniffName"/>
```

**3. Tag a new release and push:**

```bash
git add .
git commit -m "Add YourSniffName sniff"
git tag x.x.x
git push && git push origin x.x.x
```

Then update globally to pick up the new version:

```bash
composer global update pieweb/internal-php-coding-standards
```
