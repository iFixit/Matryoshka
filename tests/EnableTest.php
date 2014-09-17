<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class EnableTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\Enable(new Matryoshka\Ephemeral());
   }

   public function testEnabled() {
      $backend = $this->getBackend();
      list($key, $value) = $this->getRandomKeyValue();

      $backend->writesEnabled = false;
      $this->assertFalse($backend->set($key, $value));
      $this->assertNull($backend->get($key));

      $this->assertFalse($backend->add($key, $value));
      $this->assertNull($backend->get($key));

      $this->assertFalse($backend->increment($key));
      $this->assertNull($backend->get($key));

      $this->assertFalse($backend->decrement($key));
      $this->assertNull($backend->get($key));
      $backend->writesEnabled = true;

      $this->assertTrue($backend->set($key, $value));
      $this->assertSame($value, $backend->get($key));

      $backend->getsEnabled = false;
      $this->assertNull($backend->get($key));
      $backend->getsEnabled = true;

      $backend->deletesEnabled = false;
      $this->assertFalse($backend->delete($key));
      $this->assertSame($value, $backend->get($key));
      $backend->deletesEnabled = true;
   }

   public function testEnabledGetSetMultiple() {
      $backend = $this->getBackend();
      list($key1, $value1, $id1) = $this->getRandomKeyValueId();
      list($key2, $value2, $id2) = $this->getRandomKeyValueId();

      $keys = [
         $key1 => $id1,
         $key2 => $id2
      ];

      $expectedMisses = $backend->getMultiple($keys);
      $expected = [
         $key1 => $value1,
         $key2 => $value2
      ];

      $backend->writesEnabled = false;
      $backend->setMultiple($expected);
      $this->assertSame($expectedMisses, $backend->getMultiple($keys));

      $backend->writesEnabled = true;
      $backend->setMultiple($expected);
      $this->assertSame($expected, $backend->getMultiple($keys)[0]);

      // Test getsEnabled
      $backend->getsEnabled = false;
      $this->assertSame($expectedMisses, $backend->getMultiple($keys));

      $backend->getsEnabled = true;
      $this->assertSame($expected, $backend->getMultiple($keys)[0]);
   }

   public function testEnabledSetMultiple() {
      $backend = $this->getBackend();
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();

      $backend->writesEnabled = false;

      $this->assertFalse($backend->setMultiple([
         $key1 => $value1,
         $key2 => $value2
      ]));

      $this->assertNull($backend->get($key1));
      $this->assertNull($backend->get($key2));

      $backend->writesEnabled = true;

      $this->assertTrue($backend->setMultiple([
         $key1 => $value1,
         $key2 => $value2
      ]));

      $this->assertSame($value1, $backend->get($key1));
      $this->assertSame($value2, $backend->get($key2));
   }
}
