<?php namespace de\thekid\dialog\import;

use webservices\rest\Endpoint;

abstract class Task {

  /** Executes this task */
  public abstract function execute(Endpoint $api);

  /** Returns a task description */
  public abstract function description(): string;
}