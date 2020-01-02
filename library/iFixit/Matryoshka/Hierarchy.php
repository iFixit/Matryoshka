<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Cache hierarchy.
 *
 * Note: This Backend is currently experimental. There are many situations
 * that result in nonobvious and unexpected behavior. Read the comments and
 * take care before using this class.
 */
class Hierarchy extends Backend {
   /** @var Backend[] */
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

   public function increment($key, $amount = 1, $expiration = 0) {
      return $this->doIncrement(__FUNCTION__, $key, $amount, $expiration);
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      return $this->doIncrement(__FUNCTION__, $key, $amount, $expiration);
   }

   // TODO: This gets kinda awkward. This assumes that the last backend is the
   // authoritative source of the value so the incremented value is set on all
   // of the other backends but the results of those sets aren't verified. It
   // also resets the expiration time.
   private function doIncrement(string $method, string $key, int $amount = 1, int $expiration = 0) {
      if (empty($this->backends)) {
         return false;
      }

      $lastBackend = $this->backends[$this->backendCount - 1];
      $newValue = $lastBackend->$method($key, $amount, $expiration);

      if ($newValue === false) {
         return false;
      }

      // Set the value on all the other backends.
      for ($i = 0; $i < $this->backendCount - 1; $i++) {
         $this->backends[$i]->set($key, $newValue, $expiration);
      }

      return $newValue;
   }

   public function get($key) {
      $value = null;
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
      if (empty($keys)) {
         return [[],[]];
      }

      $missed = $keys;
      $found = [];

      for ($i = 0; $i < $this->backendCount; $i++) {
         [$newFound, $missed] = $this->backends[$i]->getMultiple($missed);

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

   public function deleteMultiple(array $keys) {
      $success = true;
      foreach ($this->backends as $backend) {
         $success = $backend->deleteMultiple($keys) && $success;
      }

      return $success;
   }
}
