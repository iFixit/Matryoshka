<?php

namespace iFixit\Matryoshka;

/**
 * Refresh the cache once per key per request.
 */
class DisableCacheGets extends Backend {
   private Backend $backend;
   private array $gets;

   public function __construct(Backend $backend) {
      $this->backend = $backend;
      $this->gets = [];
   }

   public function get($key) {
      if ($this->gets[$key] ?? null) {
         return $this->backend->get($key);
      } else {
         $this->gets[$key] = true;
         return self::MISS;
      }
   }

   public function getMultiple(array $keys) {
      $found = [];
      foreach ($keys as $key => $_) {
         if ($this->gets[$key] ?? null) {
            $found[$key] = $this->backend->get($key);
         } else {
            $this->gets[$key] = true;
            $found[$key] = self::MISS;
         }
      }
      return [$found, $keys];
   }
}
