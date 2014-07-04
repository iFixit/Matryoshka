<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class LocalTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\Local(new Matryoshka\MemoryArray());
   }
}
