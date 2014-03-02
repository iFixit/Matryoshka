<?php

namespace iFixit\Smeagol\Backends;

use iFixit\Smeagol;

abstract class KeyChanger extends Smeagol\Backends\Backend {
   private $backend;

   public abstract function changeKey($key);

   public function __construct(Smeagol\Backends\Backend $backend) {
      $this->backend = $backend;
   }

   public function set($key, $value, $expiration = 0) {
      $this->backend->set($this->changeKey($key), $value, $expiration);
   }

   public function get($key) {
      return $this->backend->get($this->changeKey($key));
   }

   public function delete($key) {
      $this->backend->delete($this->changeKey($key));
   }
}
