<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

abstract class KeyChange extends BackendWrap {
   public abstract function changeKey($key);

   /**
    * Convert many keys at once.
    *
    * @param array $keys An array of [key => id].
    *
    * @return array An array of [changedKey => id].
    */
   public abstract function changeKeys(array $keys);

   public function set($key, $value, $expiration = 0) {
      return $this->backend->set($this->changeKey($key), $value, $expiration);
   }

   public function setMultiple(array $values, $expiration = 0) {
      return $this->backend->setMultiple($this->changeKeys($values), $expiration);
   }

   public function add($key, $value, $expiration = 0) {
      return $this->backend->add($this->changeKey($key), $value, $expiration);
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      return $this->backend->increment($this->changeKey($key), $amount,
       $expiration);
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      return $this->backend->decrement($this->changeKey($key), $amount,
       $expiration);
   }

   public function get($key) {
      return $this->backend->get($this->changeKey($key));
   }

   public function getMultiple(array $keys) {
      if (empty($keys)) {
         return [[],[]];
      }

      // Ignore the missed values -- we will recompute them later.
      list($found) = $this->backend->getMultiple($this->changeKeys($keys));

      // Take advantage of the guaranteed ordering of the keys to unchange them.
      $searchKeys = array_keys($keys);
      $foundKeys = array_keys($found);
      $unChangedFound = [];
      $unChangedMissed = [];

      foreach ($searchKeys as $i => $searchKey) {
         $value = $found[$foundKeys[$i]];
         $unChangedFound[$searchKey] = $value;
         if ($value === self::MISS) {
            $unChangedMissed[$searchKey] = $keys[$searchKey];
         }
      }

      return [$unChangedFound, $unChangedMissed];
   }

   public function delete($key) {
      return $this->backend->delete($this->changeKey($key));
   }

   public function deleteMultiple(array $keys) {
      // changeKeys works on [key => id, ...] rather than [key, ...] so we
      // have to flip it and flip it back.
      $changedKeys = array_flip($this->changeKeys(array_flip($keys)));
      return $this->backend->deleteMultiple($changedKeys);
   }
}
