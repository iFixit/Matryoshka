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

   public function addScope(Scope $scoped) {
      $this->scopes[] = $scoped;
      $this->sortScopes();

      return $this;
   }

   public function changeKey($key) {
      $scopedKey = '';

      foreach ($this->scopes as $scope) {
         $scopedKey .= $scope->getScopePrefix() . '-';
      }

      return "{$scopedKey}{$key}";
   }

   private function sortScopes() {
      usort($this->scopes, function($a, $b) {
         return strcmp($a->getScopeName(), $b->getScopeName());
      });
   }
}
