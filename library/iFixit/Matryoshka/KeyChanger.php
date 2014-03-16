<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

abstract class KeyChanger extends Backend {
   private $backend;

   public abstract function changeKey($key);

   public function __construct(Backend $backend) {
      $this->backend = $backend;
   }

   public function set($key, $value, $expiration = 0) {
      return $this->backend->set($this->changeKey($key), $value, $expiration);
   }

   public function add($key, $value, $expiration = 0) {
      return $this->backend->add($this->changeKey($key), $value, $expiration);
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      return $this->backend->increment($this->changeKey($key), $amount,
       $expiration);
   }

   public function get($key) {
      return $this->backend->get($this->changeKey($key));
   }

   public function getMultiple(array $keys) {
      $prefixedKeys = [];

      foreach ($keys as $key => $id) {
         $prefixedKeys[$this->changeKey($key)] = $id;
      }

      // Ignore the missed values -- we will recompute them later.
      list($found) = $this->backend->getMultiple($prefixedKeys);

      // Take advantage of the guaranteed ordering of the keys to unprefix them.
      $searchKeys = array_keys($keys);
      $foundKeys = array_keys($found);
      $unPrefixedFound = [];
      $unPrefixedMissed = [];

      foreach ($searchKeys as $i => $searchKey) {
         $value = $found[$foundKeys[$i]];
         $unPrefixedFound[$searchKey] = $value;
         if ($value === self::MISS) {
            $unPrefixedMissed[$searchKey] = $keys[$searchKey];
         }
      }

      return [$unPrefixedFound, $unPrefixedMissed];
   }

   public function delete($key) {
      return $this->backend->delete($this->changeKey($key));
   }
}
