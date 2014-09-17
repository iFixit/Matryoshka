<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class MultiScopeTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\MultiScope(
         new Matryoshka\Ephemeral(),
         [
            new Matryoshka\Scope(new Matryoshka\Ephemeral(), 'scope1'),
            new Matryoshka\Scope(new Matryoshka\Ephemeral(), 'scope2')
         ]
      );
   }

   public function testMultiScopeBadArgument() {
      try {
         new Matryoshka\MultiScope(new Matryoshka\Ephemeral(), ['string']);
         $this->fail("Doesn't throw InvalidArgumentException");
      } catch (InvalidArgumentException $e) {
         // Do nothing.
      }
   }

   public function testMultiScope() {
      $memArray = new Matryoshka\Ephemeral();
      $scopes = [
         new Matryoshka\Scope($memArray, 'scope'),
         new Matryoshka\Scope(new Matryoshka\Ephemeral(), 'scope2')
      ];
      $multiScope = new Matryoshka\MultiScope($memArray, $scopes);
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();

      $multiScope->set($key1, $value1);
      $scopes[0]->deleteScope();
      $this->assertNull($multiScope->get($key1));

      $multiScope->set($key2, $value2);
      $scopes[1]->deleteScope();
      $this->assertNull($multiScope->get($key2));

      list($key3, $value3) = $this->getRandomKeyValue();
      // The order of the scopes shouldn't change the resulting keys.
      $flippedMultiScope = new Matryoshka\MultiScope($memArray,
       array_reverse($scopes));

      $flippedMultiScope->set($key3, $value3);
      $this->assertSame($value3, $flippedMultiScope->get($key3));
      $this->assertSame($value3, $multiScope->get($key3));

      $flippedMultiScope->delete($key3);
      $this->assertNull($flippedMultiScope->get($key3));
      $this->assertNull($multiScope->get($key3));
   }

   public function testSingleScope() {
      $memArray = new Matryoshka\Ephemeral();
      $scope = new Matryoshka\Scope($memArray, 'scope');
      $multiScope = new Matryoshka\MultiScope($memArray, [$scope]);

      // The MultiScope key should be the same as the sole scope that it
      // contains.
      $this->assertSame($scope->changeKey('key'), $multiScope->changeKey('key'));
   }

   public function testAddScope() {
      $ephemeral = new Matryoshka\Ephemeral();
      $scopes = [
         new Matryoshka\Scope($ephemeral, 'scope1'),
         new Matryoshka\Scope($ephemeral, 'scope2'),
         new Matryoshka\Scope($ephemeral, 'scope3')
      ];
      list($key, $value) = $this->getRandomKeyValue();
      $constructorMultiScope = new Matryoshka\MultiScope($ephemeral, $scopes);

      $this->assertTrue($constructorMultiScope->set($key, $value));

      // Should be the same as providing them all in the constructor.
      $addMultiScope = new Matryoshka\MultiScope($ephemeral, []);
      foreach ($scopes as $scope) {
         $addMultiScope->addScope($scope);
      }
      $this->assertSame($addMultiScope->get($key), $value);

      // The order they are added shouldn't matter.
      $reverseAddMultiScope = new Matryoshka\MultiScope($ephemeral, []);
      foreach (array_reverse($scopes) as $scope) {
         $reverseAddMultiScope->addScope($scope);
      }
      $this->assertSame($reverseAddMultiScope->get($key), $value);
   }
}
