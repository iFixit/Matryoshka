<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class MultiScopedTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\MultiScoped(
         new Matryoshka\MemoryArray(),
         [
            new Matryoshka\Scoped(new Matryoshka\MemoryArray(), 'scope')
         ]
      );
   }

   public function testMultiScopedBadArgument() {
      try {
         new Matryoshka\MultiScoped(new Matryoshka\MemoryArray(), ['string']);
         $this->fail("Doesn't throw InvalidArgumentException");
      } catch (InvalidArgumentException $e) {
         // Do nothing.
      }
   }

   public function testMultiScoped() {
      $memArray = new Matryoshka\MemoryArray();
      $scopes = [
         new Matryoshka\Scoped($memArray, 'scope'),
         new Matryoshka\Scoped(new Matryoshka\MemoryArray(), 'scope2')
      ];
      $multiScoped = new Matryoshka\MultiScoped($memArray, $scopes);
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();

      $multiScoped->set($key1, $value1);
      $scopes[0]->deleteScope();
      $this->assertNull($multiScoped->get($key1));

      $multiScoped->set($key2, $value2);
      $scopes[1]->deleteScope();
      $this->assertNull($multiScoped->get($key2));

      list($key3, $value3) = $this->getRandomKeyValue();
      // The order of the scopes shouldn't change the resulting keys.
      $flippedMultiScoped = new Matryoshka\MultiScoped($memArray,
       array_reverse($scopes));

      $flippedMultiScoped->set($key3, $value3);
      $this->assertSame($value3, $flippedMultiScoped->get($key3));
      $this->assertSame($value3, $multiScoped->get($key3));

      $flippedMultiScoped->delete($key3);
      $this->assertNull($flippedMultiScoped->get($key3));
      $this->assertNull($multiScoped->get($key3));
   }
}
