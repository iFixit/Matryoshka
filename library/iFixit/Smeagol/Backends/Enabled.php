<?php

namespace iFixit\Smeagol\Backends;

use iFixit\Smeagol;

/**
 * Allows disabling gets/sets/deletes/etc.
 */
class Enabled extends Backend {
   private $backend;
   public $getsEnabled;
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
         $this->backend->set($key, $value, $expiration);
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
         $this->backend->delete($key);
      }
   }
}
