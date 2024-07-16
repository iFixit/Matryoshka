<?php

namespace iFixit\Matryoshka;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;

class PSR16Adapter implements CacheInterface {
   private Scope $scope;

   public function __construct(Backend $backend, string $scopeName) {
      $this->scope = new Scope($backend, $scopeName);
   }

   public function get(string $key, mixed $default = null): mixed {
      return $this->scope->get($key) ?: $default;
   }

   public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool {
      return $this->scope->set($key, $value, $this->getSeconds($ttl));
   }

   public function delete(string $key): bool {
      return $this->scope->delete($key);
   }

   public function clear(): bool {
      return $this->scope->deleteScope();
   }

   public function getMultiple(iterable $keys, mixed $default = null): iterable {
      [$found, $missed] = $this->scope->getMultiple(iterator_to_array($keys));
      $missedWithDefault = [];

      foreach ($missed as $key => $_value) {
         $missedWithDefault[$key] = $default;
      }

      return [
         ...$found,
         ...$missedWithDefault,
      ];
   }

   public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool {
      return $this->scope->setMultiple(iterator_to_array($values), $this->getSeconds($ttl));
   }

   public function deleteMultiple(iterable $keys): bool {
      return $this->scope->deleteMultiple(iterator_to_array($keys));
   }

   public function has(string $key): bool {
      $isMiss = $this->scope->get($key) === Backend::MISS;
      return !$isMiss;
   }

   private function getSeconds(null|int|DateInterval $secs) {
      $seconds = $secs instanceof DateInterval ? (new DateTime('@0'))->add($secs)->getTimestamp() : $secs;
      return $seconds ?: 0;
   }
}
