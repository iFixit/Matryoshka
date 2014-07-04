<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class HierarchyTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\Hierarchy([
         new Matryoshka\MemoryArray(),
         new Matryoshka\MemoryArray()
      ]);
   }

   public function testHierarchy() {
      $backends = [
         new Matryoshka\MemoryArray(),
         // Use Memcache as the backend if it exists, otherwise just use an
         // in memory array.
         Matryoshka\Memcache::isAvailable() ?
          Matryoshka\Memcache::create($this->getMemcache()) :
          new Matryoshka\MemoryArray()
      ];
      $hierarchy = new Matryoshka\Hierarchy($backends);
      $allBackends = array_merge($backends, [$hierarchy]);
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($hierarchy->get($key));

      $this->assertTrue($hierarchy->set($key, $value));

      foreach ($allBackends as $backend) {
         $this->assertSame($value, $backend->get($key));
      }

      $this->assertTrue($backends[0]->delete($key));

      $this->assertSame($value, $hierarchy->get($key));
      $this->assertSame($value, $backends[0]->get($key));

      $this->assertTrue($hierarchy->delete($key));

      foreach ($allBackends as $backend) {
         $this->assertNull($backend->get($key));
      }

      list($key, $value) = $this->getRandomKeyValue();
      $this->assertTrue($hierarchy->set($key, $value));

      $this->assertTrue($backends[1]->delete($key));
      $this->assertSame($value, $hierarchy->get($key));
   }
}
