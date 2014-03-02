<?php

namespace iFixit\Smeagol\Backends;

use iFixit\Smeagol;

/**
 * Base class for cache backends.
 */
abstract class Backend {
   const MISS = null;

   public abstract function set($key, $value);
   public abstract function get($key);
   public abstract function delete($key);

   public function getAndSet($key, callable $callback) {
      $value = $this->get($key);

      if ($value === null) {
         $value = $callback();
         $this->set($key, $value);
      }

      return $value;
   }
}
