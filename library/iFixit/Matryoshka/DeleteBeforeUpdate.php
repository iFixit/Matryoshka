<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Ensures that a delete is issued before trying to update an existing key.
 *
 * This layer is useful for working with mcrouter. mcrouter provides a reliable
 * delete stream, but can't guarantee updates are recorded.
 */
class DeleteBeforeUpdate extends BackendWrap {
   public function set($key, $value, $expiration = 0) {
      $addResponse = $this->backend->add($key, $value, $expiration);
      if ($addResponse === false) {
         $this->backend->delete($key);
         return $this->backend->set($key, $value, $expiration);
      }
      return $addResponse;
   }

   public function setMultiple(array $values, $expiration = 0) {
      foreach ($values as $key => $value) {
         $this->set($key, $value, $expiration);
      }
      return true;
   }
}
