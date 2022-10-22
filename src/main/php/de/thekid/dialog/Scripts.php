<?php namespace de\thekid\dialog;

use io\{Path, File, Files};
use web\frontend\helpers\Extension;

/**
 * Script development helper inlines JavaScript files for development
 * purposes and falls back to the bundled versions for production. This
 * saves a bundling step during development.
 * 
 * The following will yield the contents of `suggestions.js` in place:
 * ```html
 * <script type="module">
 *   {{&use 'suggestions'}}
 *   suggestions(document.querySelector('#search'));
 * </script>
 * ```
 */ 
class Scripts extends Extension {

  /** Creates a new instance given a path for the script files and the development mode */
  public function __construct(private string|Path $base, private bool $development) { } 

  /** @return iterable */
  public function helpers() {
    if ($this->development) {
      yield 'use' => function($node, $context, $options) {
        return Files::read(new File($this->base, $options[0].'.js'));
      };
      return;
    }

    // Explicitely declare `use` helper as NOOP
    yield 'use' => null;
  }
}