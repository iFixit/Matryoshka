<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Faster version of:
 *
 * $cache = new Matryoshka\Hierarchy([
 *    new Matryoshka\Ephemeral(),
 *    new Matryoshka\Memcache()
 * ]);
 */
class Local extends Backend {
   private $backend;
   private $cache;

   public function __construct(Backend $backend) {
      $this->backend = $backend;
      $this->cache = [];
   }

   public function set($key, $value, $expiration = 0) {
      $success = $this->backend->set($key, $value, $expiration);

      if ($success) {
         $this->cache[$key] = $value;
      }

      return $success;
   }

   public function add($key, $value, $expiration = 0) {
      $success = $this->backend->add($key, $value, $expiration);

      if ($success) {
         $this->cache[$key] = $value;
      }

      return $success;
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      $result = $this->backend->increment($key, $amount, $expiration);

      if ($result !== false) {
         $this->cache[$key] = $result;
      }

      return $result;
   }

   public function get($key) {
      if (array_key_exists($key, $this->cache)) {
         return $this->cache[$key];
      } else {
         $result = $this->backend->get($key);

         if ($result !== self::MISS) {
            $this->cache[$key] = $result;
         }

         return $result;
      }
   }

   public function getMultiple(array $keys) {
      $localFound = array_intersect_key($this->cache, $keys);
      $localMissing = array_diff_key($keys, $localFound);

      list($backendFound, $backendMissing) =
       $this->backend->getMultiple($localMissing);

      // Merge the hits into the local cache.
      foreach ($backendFound as $key => $value) {
         if ($value !== self::MISS) {
            $this->cache[$key] = $value;
         }
      }

      // Merge in all of the values starting with the provided keys, then the
      // local values, then the backend values (including misses). This will
      // preserve the key order.
      return [array_merge($keys, $localFound, $backendFound),
       $backendMissing];
   }

   public function delete($key) {
      $success = $this->backend->delete($key);

      // Always unset the local version because the key may have existed on
      // the backend.
      unset($this->cache[$key]);

      return $success;
   }

   public function setMultiple(array $values, $expiration = 0) {
      $success = true;

      foreach ($values as $key => $value) {
         if ($this->backend->set($key, $value, $expiration)) {
            $this->cache[$key] = $value;
         } else {
            $success = false;
         }
      }

      return $success;
   }
}
