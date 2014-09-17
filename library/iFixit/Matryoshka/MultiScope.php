<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class MultiScope extends KeyChange {
   private $scopes;

   public function __construct(Backend $backend, array $scopes = []) {
      parent::__construct($backend);

      foreach ($scopes as $scope) {
         if (!($scope instanceof Scope)) {
            $type = is_object($scope) ? get_class($scope) : gettype($scope);
            throw new \InvalidArgumentException(
             "Not an instance of Scope: $type");
         }
      }

      $this->scopes = $scopes;
      $this->sortScopes();
   }

   public function addScope(Scope $scope) {
      $this->scopes[] = $scope;
      $this->sortScopes();

      return $this;
   }

   public function changeKey($key) {
      $scopePrefix = $this->getScopePrefix();

      return "{$scopePrefix}{$key}";
   }

   public function changeKeys(array $keys) {
      $scopePrefix = $this->getScopePrefix();
      $changedKeys = [];

      foreach ($keys as $key => $value) {
         $changedKeys["{$scopePrefix}{$key}"] = $value;
      }

      return $changedKeys;
   }

   private function getScopePrefix() {
      $scopePrefix = '';

      foreach ($this->scopes as $scope) {
         $scopePrefix .= $scope->getScopePrefix();
      }

      return $scopePrefix;
   }

   private function sortScopes() {
      usort($this->scopes, function($a, $b) {
         return strcmp($a->getScopeName(), $b->getScopeName());
      });
   }
}
