<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Modifies all expiration times using the provided function.
 */
class ExpirationChange extends BackendWrap {
   private $changeExpiration;

   public function __construct(Backend $backend, callable $changeExpiration) {
      parent::__construct($backend);
      $this->changeExpiration = $changeExpiration;
   }

   public function set($key, $value, $expiration = 0) {
      return $this->backend->set($key, $value,
       call_user_func($this->changeExpiration, $expiration));
   }

   public function setMultiple(array $values, $expiration = 0) {
      return $this->backend->setMultiple($values,
       call_user_func($this->changeExpiration, $expiration));
   }

   public function add($key, $value, $expiration = 0) {
      return $this->backend->add($key, $value,
       call_user_func($this->changeExpiration, $expiration));
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      return $this->backend->increment($key, $amount,
       call_user_func($this->changeExpiration, $expiration));
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      return $this->backend->decrement($key, $amount,
       call_user_func($this->changeExpiration, $expiration));
   }

   public function getAbsoluteKey($key) {
      return $this->backend->getAbsoluteKey($key);
   }
}
