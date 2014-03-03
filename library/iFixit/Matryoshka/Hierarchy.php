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

   public function delete($key) {
      $success = true;
      foreach ($this->backends as $backend) {
         $success = $backend->delete($key) && $success;
      }

      return $success;
   }
}
