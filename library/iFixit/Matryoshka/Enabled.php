<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Allows disabling gets/sets/deletes/etc.
 */
class Enabled extends Backend {
   private $backend;
   public $getsEnabled;
   // TODO: Rename to writesEnabled because it covers more than just `set`?
   public $setsEnabled;
   public $deletesEnabled;

   public function __construct(Backend $backend) {
      $this->backend = $backend;
      $this->getsEnabled = true;
      $this->setsEnabled = true;
      $this->deletesEnabled = true;
   }

   public function set($key, $value, $expiration = 0) {
      if ($this->setsEnabled) {
         return $this->backend->set($key, $value, $expiration);
      } else {
         return false;
      }
   }

   public function add($key, $value, $expiration = 0) {
      if ($this->setsEnabled) {
         return $this->backend->add($key, $value, $expiration);
      } else {
         return false;
      }
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      if ($this->setsEnabled) {
         return $this->backend->increment($key, $amount, $expiration);
      } else {
         return false;
      }
   }

   public function get($key) {
      if ($this->getsEnabled) {
         return $this->backend->get($key);
      } else {
         return self::MISS;
      }
   }

   public function delete($key) {
      if ($this->deletesEnabled) {
         return $this->backend->delete($key);
      } else {
         return false;
      }
   }
}
