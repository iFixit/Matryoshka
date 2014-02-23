<?php

namespace iFixit\Smeagol\Backends;

use iFixit\Smeagol;

/**
 * Base class for cache backends.
 */
abstract class Backend {
   public abstract function set($key, $value);
   public abstract function get($key);
   public abstract function delete($key);

   public function miss() {
      return null;
   }
}
