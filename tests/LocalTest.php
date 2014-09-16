<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class LocalTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\Local(new Matryoshka\Ephemeral());
   }

   public function testLocal() {
      $ephemeral = new Matryoshka\Ephemeral();
      $cache = new Matryoshka\Local($ephemeral);

      list($key1, $value1) = $this->getRandomKeyValue();

      $cache->set($key1, $value1);
      $ephemeral->delete($key1);
      $this->assertSame($value1, $cache->get($key1));
      $cache->clear();
      $this->assertNull($cache->get($key1));

      list($key2, $value2) = $this->getRandomKeyValue();

      $ephemeral->set($key2, $value2);
      $cache->get($key2);
      $ephemeral->delete($key2);
      $this->assertSame($value2, $cache->get($key2));

      $cache->delete($key2);
      $this->assertNull($cache->get($key2));
   }

   public function testLocalGetMultiple() {
      $ephemeral = new Matryoshka\Ephemeral();
      $cache = new TestLocal($ephemeral);

      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();

      $ephemeral->set($key1, $value1);

      list($found, $missing) = $cache->getMultiple(
         [$key1 => 'key1',  $key2 => 'key2']);
      $this->assertSame(
         [$key1 => $value1, $key2 => null], $found);
      $this->assertSame(
         [$key2 => 'key2'], $missing);
      $this->assertSame($cache->getCache(),
         [$key1 => $value1]);

      $ephemeral->set($key2, $value2);

      list($found, $missing) = $cache->getMultiple(
         [$key1 => 'key1',  $key2 => 'key2']);
      $this->assertSame(
         [$key1 => $value1, $key2 => $value2], $found);
      $this->assertSame(
         [], $missing);
      $this->assertSame($cache->getCache(),
         [$key1 => $value1, $key2 => $value2]);
   }
}
