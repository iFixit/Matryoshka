<?php

require_once __DIR__ . '/../library/iFixit/Smeagol.php';

\iFixit\Smeagol::autoload();

class SmeagolTest extends PHPUnit_Framework_TestCase {
   public function testMemoryArray() {
      $cache = new \iFixit\Smeagol\Backends\MemoryArray();

      $key = 'test';
      $value = 'value';
      $this->assertNull($cache->get($key));

      $cache->set($key, $value);

      $this->assertSame($value, $cache->get($key));

      $cache->delete($key);

      $this->assertNull($cache->get($key));
   }
}
