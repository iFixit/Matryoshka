<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class EnabledTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\Enabled(new Matryoshka\MemoryArray());
   }

   public function testEnabled() {
      $backend = $this->getBackend();
      list($key, $value) = $this->getRandomKeyValue();

      $backend->setsEnabled = false;
      $this->assertFalse($backend->set($key, $value));
      $this->assertNull($backend->get($key));
      $backend->setsEnabled = true;

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
}
