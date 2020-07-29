<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Memcached extends Backend {
   const MAX_KEY_LENGTH = 250;

   protected $memcached;

   private const ERRORS = [
      \Memcached::RES_FAILURE => 'The operation failed in some fashion.',
      \Memcached::RES_HOST_LOOKUP_FAILURE => 'DNS lookup failed.',
      \Memcached::RES_UNKNOWN_READ_FAILURE => 'Failed to read network data.',
      \Memcached::RES_PROTOCOL_ERROR => 'Bad command in memcached protocol.',
      \Memcached::RES_CLIENT_ERROR => 'Error on the client side.',
      \Memcached::RES_SERVER_ERROR => 'Error on the server side.',
      \Memcached::RES_WRITE_FAILURE => 'Failed to write network data.',
      \Memcached::RES_DATA_EXISTS => 'Failed to do compare-and-swap: item you are trying to store has been modified since you last fetched it.',
      \Memcached::RES_NOTSTORED => 'Item was not stored: but not because of an error. This normally means that either the condition for an "add" or a "replace" command was not met, or that the item is in a delete queue.',
      \Memcached::RES_PARTIAL_READ => 'Partial network data read error.',
      \Memcached::RES_SOME_ERRORS => 'Some errors occurred during multi-get.',
      \Memcached::RES_NO_SERVERS => 'Server list is empty.',
      \Memcached::RES_END => 'End of result set.',
      \Memcached::RES_ERRNO => 'System error.',
      \Memcached::RES_BUFFERED => 'The operation was buffered.',
      \Memcached::RES_TIMEOUT => 'The operation timed out.',
      \Memcached::RES_BAD_KEY_PROVIDED => 'Bad key.',
      \Memcached::RES_CONNECTION_SOCKET_CREATE_FAILURE => 'Failed to create network socket.',
      \Memcached::RES_PAYLOAD_FAILURE => 'Payload failure: could not compress/decompress or serialize/unserialize the value.',
      \Memcached::RES_AUTH_PROBLEM => 'Auth Problem',
      \Memcached::RES_AUTH_FAILURE => 'Auth Failure',
      \Memcached::RES_AUTH_CONTINUE => 'Auth Continue',
      \Memcached::RES_E2BIG => 'E2big',
      \Memcached::RES_KEY_TOO_BIG => 'Key Too Big',
      \Memcached::RES_SERVER_TEMPORARILY_DISABLED => 'Server Temporarily Disabled',
      \Memcached::RES_SERVER_MEMORY_ALLOCATION_FAILURE => 'Server Memory Allocation Failure',
   ];

   public static function isAvailable() {
      return class_exists('\Memcached', false);
   }

   /**
    * Factory method. This forces Memcached to always be wrapped in a
    * KeyShorten to fix keys that are too long and would otherwise get
    * truncated.
    */
   public static function create(\Memcached $memcached) {
      return new KeyFix(new self($memcached), self::MAX_KEY_LENGTH);
   }

   protected function __construct(\Memcached $memcached) {
      $this->memcached = $memcached;
   }

   public function set($key, $value, $expiration = 0) {
      return $this->memcached->set($key, $value, $expiration);
   }

   public function setMultiple(array $values, $expiration = 0) {
      return $this->memcached->setMulti($values, $expiration);
   }

   public function add($key, $value, $expiration = 0) {
      return $this->memcached->add($key, $value, $expiration);
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      // Memcache doesn't support negative amounts for decrement or increment
      // so send it to decrement to handle it.
      if ($amount < 0) {
         return $this->decrement($key, -$amount, $expiration);
      }

      return $this->memcached->increment($key, $amount, /* initial */ $amount,
       $expiration);
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      // Memcached doesn't support negative amounts for decrement or increment
      // so send it to increment to handle it.
      if ($amount < 0) {
         return $this->increment($key, -$amount, $expiration);
      }

      return $this->memcached->decrement($key, $amount, /* initial */ $amount,
       $expiration);
   }

   /**
    * @template T
    * @return T|self::MISS
    */
   public function get($key) {
      /** @var T|false */
      $value = $this->memcached->get($key);
      $result = $this->memcached->getResultCode();

      if ($this->isMiss($result)) {
         return self::MISS;
      }

      if (!$this->isSuccess($result)) {
         throw $this->getError($result);
      }

      /** @var T */
      return $value;
   }

   private function isMiss(int $resultCode): bool {
      return $resultCode === \Memcached::RES_NOTFOUND;
   }

   private function isSuccess(int $resultCode): bool {
      return $resultCode === \Memcached::RES_SUCCESS;
   }

   private function getError(int $resultCode): \MemcachedException {
      $hasKey = array_key_exists($resultCode, self::ERRORS);
      $defaultMessage = "An unknown Memcached error occurred. Result Code: {$resultCode}";
      $errorMessage = $hasKey ? self::ERRORS[$resultCode] : $defaultMessage;
      return new \MemcachedException($errorMessage);
   }

   public function getMultiple(array $keys) {
      if (empty($keys)) {
         return [[],[]];
      }

      /**
       * \Memcached::GET_PRESERVE_ORDER makes it so all keys are returned in
       * the order that they were requested with null indicating a miss which
       * is exactly what is needed for the found array.
       */
      $found = $this->memcached->getMulti(array_keys($keys),
       \Memcached::GET_PRESERVE_ORDER);

      $missed = [];
      foreach ($keys as $key => $id) {
         if ($found[$key] === self::MISS) {
            $missed[$key] = $id;
         }
      }

      return [$found, $missed];
   }

   public function delete($key) {
      return $this->memcached->delete($key);
   }

   public function deleteMultiple(array $keys) {
      // Some environments (HHVM) don't implement deleteMulti so we need to
      // roll it ourselves.
      if (!method_exists($this->memcached, 'deleteMulti')) {
         $success = true;
         foreach ($keys as $key) {
            $success = $this->memcached->delete($key) && $success;
         }

         return $success;
      }

      $results = $this->memcached->deleteMulti($keys);

      foreach ($results as $key => $success) {
         if ($success !== true) {
            return false;
         }
      }

      return true;
   }
}
