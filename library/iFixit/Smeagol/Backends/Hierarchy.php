<?php

namespace iFixit\Smeagol\Backends;

use iFixit\Smeagol;

/**
 * Cache hierarchy.
 */
class Hierarchy extends Smeagol\Backends\Backend {
   private $backends;
   private $backendCount;

   public function __construct(array $backends) {
      $this->backends = $backends;
      $this->backendCount = count($this->backends);
   }

   public function set($key, $value) {
      foreach ($this->backends as $backend) {
         $backend->set($key, $value);
      }
   }

   public function get($key) {
      for ($i = 0; $i < $this->backendCount; $i++) {
         $value = $this->backends[$i]->get($key);

         if ($value !== null) {
            for ($j = 0; $j < $i; $j++) {
               $this->backends[$j]->set($key, $value);
            }

            break;
         }
      }

      return $value;
   }

   public function delete($key) {
      foreach ($this->backends as $backend) {
         $backend->delete($key);
      }
   }
}
