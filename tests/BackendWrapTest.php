<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class BackendWrapTest extends AbstractBackendTest {
   protected function getBackend() {
      return new BackendWrapDummy(new Matryoshka\Ephemeral());
   }
}

class BackendWrapDummy extends Matryoshka\BackendWrap {}
