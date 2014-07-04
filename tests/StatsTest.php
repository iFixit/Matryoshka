<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class StatsTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\Stats(new Matryoshka\MemoryArray());
   }

   public function testStats() {
      $backend = $this->getBackend();
      list($key, $value) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();

      foreach ($backend->getStats() as $stat => $value) {
         $this->assertSame(0, $value, $stat);
      }

      $start = microtime(true);

      $backend->add($key, $value);
      $backend->set($key, 5);
      $backend->increment($key, 1);
      $backend->decrement($key, 1);
      $backend->get($key);
      $backend->getMultiple([$key => '', $key2 => '']);
      $backend->delete($key);

      $end = microtime(true);
      $maxTime = $end - $start;

      foreach ($backend->getStats() as $stat => $value) {
         if ($stat == 'getMultiple_key_count') {
            $this->assertSame(2, $value, $stat);
         } else if (strpos($stat, '_count') !== false) {
            $this->assertSame(1, $value, $stat);
         } else if (strpos($stat, '_time') !== false) {
            $this->assertGreaterThan(0, $value, $stat);
            $this->assertLessThan($maxTime, $value, $stat);
         }
      }

      $backend->get($key);
      $stats = $backend->getStats();

      $this->assertSame(2, $stats['get_count']);
      $this->assertSame(1, $stats['get_hit_count']);
   }
}
