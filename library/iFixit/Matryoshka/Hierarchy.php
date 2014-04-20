<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Cache hierarchy.
 */
class Hierarchy extends Backend {
   private $backends;
   private $backendCount;

   public function __construct(array $backends) {
      $this->backends = $backends;
      $this->backendCount = count($this->backends);
   }

   public function set($key, $value, $expiration = 0) {
      $success = true;
      foreach ($this->backends as $backend) {
         $success = $backend->set($key, $value, $expiration) && $success;
      }

      return $success;
   }

   public function add($key, $value, $expiration = 0) {
      $success = true;
      foreach ($this->backends as $backend) {
         $success = $backend->add($key, $value, $expiration) && $success;
      }

      return $success;
   }

   // TODO: This gets kinda awkward. This assumes that the last backend is the
   // authoritative source of the value so the incremented value is set on all
   // of the other backends but the results of those sets aren't verified. It
   // also resets the expiration time.
   public function increment($key, $amount = 1, $expiration = 0) {
      if (empty($this->backends)) {
         return false;
      }

      $numBackends = count($this->backends);
      $lastBackend = $this->backends[$numBackends - 1];
      $newValue = $lastBackend->increment($key, $amount, $expiration);

      if ($newValue === false) {
         return false;
      }

      // Set the value on all the other backends.
      for ($i = 0; $i < $numBackends - 1; $i++) {
         $this->backends[$i]->set($key, $newValue, $expiration);
      }

      return $newValue;
   }

   public function get($key) {
      for ($i = 0; $i < $this->backendCount; $i++) {
         $value = $this->backends[$i]->get($key);

         if ($value !== self::MISS) {
            for ($j = 0; $j < $i; $j++) {
               // TODO: This doesn't have an expiration time because we don't
               // know what it is on a get.
               $this->backends[$j]->set($key, $value);
            }

            break;
         }
      }

      return $value;
   }

   public function getMultiple(array $keys) {
      $missed = $keys;
      $found = [];

      for ($i = 0; $i < $this->backendCount; $i++) {
         list($newFound, $missed) = $this->backends[$i]->getMultiple($missed);

         // Remove misses.
         foreach ($newFound as $key => $value) {
            if ($value === self::MISS) {
               unset($newFound[$key]);
            }

            $found[$key] = $value;
         }

         // Set the found elements in the earlier backends.
         for ($j = 0; $j < $i; $j++) {
            $this->backends[$j]->setMultiple($newFound);
         }

         if (empty($missed)) {
            break;
         }
      }

      return [$found, $missed];
   }

   public function setMultiple(array $values, $expiration = 0) {
      $success = true;
      foreach ($this->backends as $backend) {
         $success = $backend->setMultiple($values, $expiration) && $success;
      }

      return $success;
   }

   public function delete($key) {
      $success = true;
      foreach ($this->backends as $backend) {
         $success = $backend->delete($key) && $success;
      }

      return $success;
   }
}
