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

   public function delete($key) {
      return $this->backend->delete($this->changeKey($key));
   }
}
