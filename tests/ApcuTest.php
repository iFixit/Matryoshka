<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class ApcuTest extends AbstractBackendTest {
   protected function setUp(): void {
      if (!Matryoshka\APCu::isAvailable()) {
         $this->markTestSkipped('Backend not available!');
      }

      parent::setUp();
   }

   protected function getBackend() {
      return new Matryoshka\APCu();
   }
}
